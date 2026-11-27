<?php

namespace App\Service;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Applied to every log record (all channels/handlers): masks credentials in the
 * message and the context so that no token/password ever reaches the log files.
 */
#[AsMonologProcessor]
final class SensitiveDataLogProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: LogSanitizer::sanitizeString($record->message),
            context: LogSanitizer::sanitize($record->context),
            extra: LogSanitizer::sanitize($record->extra),
        );
    }
}
