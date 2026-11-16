<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Myddleware 4.3 -> 4.4: document ids are now VARCHAR(36) (UUID) instead of VARCHAR(255).
 *
 * "doctrine:schema:update" can't change these columns on its own: MySQL refuses to modify a column used
 * in a foreign key (error 1833 "Cannot change column 'id': used in a foreign key constraint").
 * The document id and the columns referencing it are therefore changed here, with the foreign key checks
 * disabled for the duration of the ALTER statements. The rest of the schema is left to "doctrine:schema:update".
 *
 * Columns already in the expected format are skipped, so the migration can be re-run safely.
 */
final class Version20261116130000 extends AbstractMigration
{
    private const MAX_LENGTH = 36;

    // table => [column => definition], same definitions as the 4.4 mapping
    private const COLUMNS = [
        'document' => ['id' => 'VARCHAR(36) NOT NULL'],
        'documentdata' => ['doc_id' => 'VARCHAR(36)'],
        'log' => ['doc_id' => 'VARCHAR(36)'],
        'workflowlog' => ['trigger_document_id' => 'VARCHAR(36)', 'generate_document_id' => 'VARCHAR(36)'],
    ];

    public function getDescription(): string
    {
        return 'Myddleware 4.4: document id and the columns referencing it changed to VARCHAR(36)';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed on MySQL / MariaDB.'
        );

        $alters = [];
        foreach (self::COLUMNS as $table => $columns) {
            $changes = [];
            foreach ($columns as $column => $definition) {
                $type = $this->connection->fetchOne(
                    'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $column]
                );
                if (false === $type) {
                    $this->write(sprintf('<comment>%s.%s not found, skipped.</comment>', $table, $column));
                    continue;
                }
                if ('varchar('.self::MAX_LENGTH.')' === strtolower((string) $type)) {
                    $this->write(sprintf('%s.%s is already %s, skipped.', $table, $column, $type));
                    continue;
                }

                // Never truncate an id
                $maxLength = (int) $this->connection->fetchOne(sprintf('SELECT MAX(CHAR_LENGTH(`%s`)) FROM `%s`', $column, $table));
                $this->abortIf(
                    $maxLength > self::MAX_LENGTH,
                    sprintf('%s.%s contains values longer than %d characters (%d), it can\'t be converted.', $table, $column, self::MAX_LENGTH, $maxLength)
                );

                $changes[] = sprintf('CHANGE `%1$s` `%1$s` %2$s', $column, $definition);
            }
            if (!empty($changes)) {
                $alters[$table] = sprintf('ALTER TABLE `%s` %s', $table, implode(', ', $changes));
            }
        }

        if (empty($alters)) {
            return;
        }

        // The foreign key checks are disabled for this session only, while the columns are changed
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($alters as $table => $sql) {
                $this->write(sprintf('  [%s] %s', date('H:i:s'), $sql));
                $this->connection->executeStatement($sql);
            }
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The document id columns can\'t be converted back to VARCHAR(255).');
    }
}
