<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Custom Twig extension to handle timezone compensation for dates.
 *
 * This extension fixes the issue where Doctrine reads dates from the database
 * with the server's timezone (e.g., Europe/Zurich) instead of UTC, even though
 * the database stores them in UTC.
 */
class DateCompensationExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_compensated', [$this, 'formatDateWithCompensation']),
        ];
    }

    /**
     * Format a date with timezone compensation.
     *
     * This filter takes a date string or DateTime object, treats it as if it were
     * in UTC (compensating for the incorrect server timezone), and then converts
     * it to the specified timezone.
     *
     * @param mixed $date The date to format (string or DateTime)
     * @param string $format The date format (e.g., 'Y-m-d H:i:s')
     * @param string $timezone The target timezone (e.g., 'UTC', 'Europe/Paris')
     * @return string The formatted date string
     */
    public function formatDateWithCompensation($date, string $format = 'Y-m-d H:i:s', string $timezone = 'UTC'): string
    {
        if (empty($date)) {
            return '';
        }

        // Convert to DateTime if it's a string
        if (is_string($date)) {
            // Parse the date string (it might come from the database as a string)
            $dateObj = new \DateTime($date);
        } elseif ($date instanceof \DateTime) {
            $dateObj = clone $date;
        } else {
            return '';
        }

        // COMPENSATION FIX: Get the raw date/time values (ignoring the incorrect server timezone)
        $dateString = $dateObj->format('Y-m-d H:i:s');

        // Create a new DateTime object, explicitly setting it to UTC
        $dateInUtc = new \DateTime($dateString, new \DateTimeZone('UTC'));

        // Convert from UTC to the target timezone
        $dateInTargetTz = clone $dateInUtc;
        $dateInTargetTz->setTimezone(new \DateTimeZone($timezone));

        // Return formatted string
        return $dateInTargetTz->format($format);
    }
}
