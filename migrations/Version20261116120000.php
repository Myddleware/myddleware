<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Myddleware 4.3 -> 4.4: convert the former Doctrine "array" columns (PHP serialized, LONGTEXT
 * with a "(DC2Type:array)" comment) to native JSON columns.
 *
 * Must run BEFORE "doctrine:schema:update": MySQL refuses to ALTER a column to JSON while it
 * still contains serialized PHP values.
 *
 * Only rows that are not valid JSON yet are converted, so the migration can be re-run safely
 * (e.g. after an interrupted run, or on rows already written by 4.4).
 *
 * Target format of each column, matching what the 4.4 entities expect:
 *  - documentdata.data         JSON object        (DocumentData::getData() re-encodes it to a string)
 *  - ruleaudit.data            JSON object        (RuleAudit::getData(): array)
 *  - workflow_audit.data       JSON string        (WorkflowAudit::getData(): ?string)
 *  - users.roles               JSON array         (User roles)
 *  - workflowaction.arguments  JSON object/array  (WorkflowAction::getArguments(): array)
 */
final class Version20261116120000 extends AbstractMigration
{
    private const BATCH_SIZE = 1000;
    private const SQL_RANGE = 50000;

    /** Value stored in 4.3 was a JSON string: keep the decoded JSON (object). */
    private const MODE_JSON_STRING_TO_OBJECT = 'json_string_to_object';
    /** Value stored in 4.3 was a JSON string: keep it as a JSON string. */
    private const MODE_JSON_STRING_TO_STRING = 'json_string_to_string';
    /** Value stored in 4.3 was a PHP array: encode it as JSON. */
    private const MODE_ARRAY = 'array';

    private const COLUMNS = [
        ['table' => 'documentdata',   'column' => 'data',      'pk' => 'id', 'mode' => self::MODE_JSON_STRING_TO_OBJECT, 'empty' => '{}'],
        ['table' => 'ruleaudit',      'column' => 'data',      'pk' => 'id', 'mode' => self::MODE_JSON_STRING_TO_OBJECT, 'empty' => '{}'],
        ['table' => 'workflow_audit', 'column' => 'data',      'pk' => 'id', 'mode' => self::MODE_JSON_STRING_TO_STRING, 'empty' => '""'],
        ['table' => 'users',          'column' => 'roles',     'pk' => 'id', 'mode' => self::MODE_ARRAY,                 'empty' => '[]'],
        ['table' => 'workflowaction', 'column' => 'arguments', 'pk' => 'id', 'mode' => self::MODE_ARRAY,                 'empty' => '[]'],
    ];

    public function getDescription(): string
    {
        return 'Myddleware 4.4: convert serialized PHP "array" columns to native JSON columns';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL statements commit implicitly; data is converted in batches instead.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed on MySQL / MariaDB.'
        );

        foreach (self::COLUMNS as $def) {
            $this->migrateColumn($def);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The JSON conversion of the former "array" columns cannot be reverted.');
    }

    private function migrateColumn(array $def): void
    {
        $table = $def['table'];
        $column = $def['column'];

        $info = $this->connection->fetchAssociative(
            'SELECT DATA_TYPE, IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        if (false === $info) {
            $this->write(sprintf('<comment>%s.%s not found, skipped.</comment>', $table, $column));

            return;
        }
        if ('json' === strtolower($info['DATA_TYPE'])) {
            $this->write(sprintf('%s.%s is already a JSON column, skipped.', $table, $column));

            return;
        }

        $this->write(sprintf('<info>Converting %s.%s (%s)...</info>', $table, $column, $def['mode']));

        // 1. Fast path in SQL for the bulk of the data: s:N:"<valid json>"; -> <valid json>
        if (self::MODE_JSON_STRING_TO_OBJECT === $def['mode']) {
            $this->convertSerializedJsonStringsInSql($def);
        }

        // 2. Everything else that is still not valid JSON is converted in PHP.
        $this->convertRemainingRowsInPhp($def);

        // 3. Safety check before changing the column type.
        $invalid = (int) $this->connection->fetchOne(sprintf(
            'SELECT COUNT(*) FROM `%s` WHERE `%s` IS NOT NULL AND NOT JSON_VALID(`%s`)',
            $table, $column, $column
        ));
        $this->abortIf($invalid > 0, sprintf('%d row(s) of %s.%s are still not valid JSON.', $invalid, $table, $column));

        // 4. Same column definition as the 4.4 mapping (NOT NULL, no DC2Type comment).
        $this->connection->executeStatement(sprintf(
            'UPDATE `%s` SET `%s` = ? WHERE `%s` IS NULL',
            $table, $column, $column
        ), [$def['empty']]);
        // The ALTER copies the whole table and can be long on big tables (no progress available here).
        $this->write(sprintf('  [%s] Changing the column type to JSON (full table copy)...', date('H:i:s')));
        $this->connection->executeStatement(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` JSON NOT NULL',
            $table, $column, $column
        ));
        $this->write(sprintf('<info>%s.%s converted to JSON.</info>', $table, $column));
    }

    private function convertSerializedJsonStringsInSql(array $def): void
    {
        $table = $def['table'];
        $column = $def['column'];
        $pk = $def['pk'];

        // Strip the s:N:" prefix and the "; suffix. The length N is ignored on purpose:
        // it is wrong in databases whose charset was converted after the data was written.
        $inner = sprintf("SUBSTRING(`%1\$s`, LOCATE(':\"', `%1\$s`) + 2, CHAR_LENGTH(`%1\$s`) - LOCATE(':\"', `%1\$s`) - 3)", $column);

        $bounds = $this->connection->fetchAssociative(sprintf('SELECT MIN(`%1$s`) AS min_id, MAX(`%1$s`) AS max_id FROM `%2$s`', $pk, $table));
        if (null === $bounds['min_id']) {
            return;
        }

        $minId = (int) $bounds['min_id'];
        $maxId = (int) $bounds['max_id'];
        $total = 0;
        $startTime = microtime(true);
        for ($start = $minId; $start <= $maxId; $start += self::SQL_RANGE) {
            $end = min($start + self::SQL_RANGE - 1, $maxId);
            $total += $this->connection->executeStatement(sprintf(
                "UPDATE `%1\$s` SET `%2\$s` = %3\$s
                 WHERE `%4\$s` BETWEEN ? AND ?
                   AND `%2\$s` LIKE 's:%%' AND `%2\$s` LIKE '%%\";'
                   AND NOT JSON_VALID(`%2\$s`)
                   AND JSON_VALID(%3\$s)",
                $table, $column, $inner, $pk
            ), [$start, $end]);
            $this->write(sprintf(
                '  [%s] %s %d -> %d / %d (%.1f %%), %d row(s) converted, %ds elapsed',
                date('H:i:s'), $pk, $start, $end, $maxId,
                100 * ($end - $minId + 1) / ($maxId - $minId + 1), $total, (int) (microtime(true) - $startTime)
            ));
        }
        $this->write(sprintf('  %d row(s) converted in SQL.', $total));
    }

    private function convertRemainingRowsInPhp(array $def): void
    {
        $table = $def['table'];
        $column = $def['column'];
        $pk = $def['pk'];

        $select = sprintf(
            'SELECT `%1$s` AS pk, `%2$s` AS val FROM `%3$s`
             WHERE `%2$s` IS NOT NULL AND NOT JSON_VALID(`%2$s`) AND `%1$s` > ?
             ORDER BY `%1$s` LIMIT %4$d',
            $pk, $column, $table, self::BATCH_SIZE
        );
        $update = sprintf('UPDATE `%s` SET `%s` = ? WHERE `%s` = ?', $table, $column, $pk);

        // Start below any possible key (integer or string primary key).
        $lastPk = is_numeric($this->connection->fetchOne(sprintf('SELECT MIN(`%s`) FROM `%s`', $pk, $table))) ? -1 : '';
        $total = 0;
        $fallbacks = 0;
        while ($rows = $this->connection->fetchAllAssociative($select, [$lastPk])) {
            $this->connection->beginTransaction();
            try {
                foreach ($rows as $row) {
                    $json = $this->convertValue((string) $row['val'], $def, $usedFallback);
                    $fallbacks += $usedFallback ? 1 : 0;
                    $this->connection->executeStatement($update, [$json, $row['pk']]);
                    $lastPk = $row['pk'];
                }
                $this->connection->commit();
            } catch (\Throwable $e) {
                $this->connection->rollBack();
                throw $e;
            }
            $total += count($rows);
            $this->write(sprintf('  [%s] %d row(s) converted in PHP (last %s: %s)', date('H:i:s'), $total, $pk, $lastPk));
        }

        $this->write(sprintf('  %d row(s) converted in PHP.', $total));
        if ($fallbacks > 0) {
            $this->write(sprintf('<comment>  %d row(s) of %s.%s could not be unserialized and were kept as raw text (JSON string).</comment>', $fallbacks, $table, $column));
        }
    }

    private function convertValue(string $raw, array $def, ?bool &$usedFallback): string
    {
        $usedFallback = false;
        if ('' === $raw) {
            return $def['empty'];
        }

        $value = @unserialize($raw, ['allowed_classes' => false]);
        if (false === $value && 'b:0;' !== $raw) {
            // Corrupted serialized string (wrong length after a charset conversion): extract it directly.
            if (preg_match('/^s:\d+:"(.*)";$/s', $raw, $matches)) {
                $value = $matches[1];
            } else {
                $usedFallback = true;

                return $this->encode($raw);
            }
        }

        if (null === $value || false === $value) {
            return $def['empty'];
        }

        switch ($def['mode']) {
            case self::MODE_JSON_STRING_TO_OBJECT:
                if (is_string($value)) {
                    json_decode($value);
                    // Keep the JSON document as is when valid, otherwise store the text as a JSON string.
                    return JSON_ERROR_NONE === json_last_error() ? $value : $this->encode($value);
                }

                return $this->encode($value);

            case self::MODE_JSON_STRING_TO_STRING:
                return $this->encode(is_string($value) ? $value : $this->encode($value));

            case self::MODE_ARRAY:
            default:
                return $this->encode(is_array($value) ? $value : [$value]);
        }
    }

    private function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
    }
}
