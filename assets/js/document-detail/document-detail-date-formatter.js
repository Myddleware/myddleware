/**
 * Date formatting utility for document detail view
 * Formats dates according to user's timezone and date format preferences
 */

export class DocumentDetailDateFormatter {
    /**
     * Convert PHP date format to JavaScript date format
     * @param {string} phpFormat - PHP date format (e.g., 'd/m/Y', 'Y-m-d')
     * @returns {object} - Object with format pattern and formatting function
     */
    static phpToJsFormat(phpFormat) {
        // Map common PHP date format characters to JavaScript equivalents
        const formatMap = {
            'd': 'DD',    // Day of month, 2 digits with leading zeros
            'j': 'D',     // Day of month without leading zeros
            'm': 'MM',    // Month, 2 digits with leading zeros
            'n': 'M',     // Month without leading zeros
            'Y': 'YYYY',  // 4 digit year
            'y': 'YY',    // 2 digit year
            'H': 'HH',    // 24-hour format with leading zeros
            'i': 'mm',    // Minutes with leading zeros
            's': 'ss',    // Seconds with leading zeros
        };

        let jsFormat = phpFormat;
        for (const [php, js] of Object.entries(formatMap)) {
            jsFormat = jsFormat.replace(new RegExp(php, 'g'), js);
        }

        return jsFormat;
    }

    /**
     * Format a date string according to user preferences
     * NOTE: Dates from the API are already in the user's timezone, we just need to reformat them
     * @param {string} dateString - Date string in Y-m-d H:i:s format (already in user's timezone)
     * @param {string} userTimezone - User's timezone (not used, kept for compatibility)
     * @param {string} userDateFormat - User's date format in PHP format (e.g., 'd/m/Y')
     * @returns {string} - Formatted date string
     */
    static formatDate(dateString, userTimezone, userDateFormat) {
        if (!dateString) {
            return '';
        }

        try {
            // Parse the date string (format: YYYY-MM-DD HH:mm:ss)
            // The date is already in the user's timezone from the backend
            const match = dateString.match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/);

            if (!match) {
                console.warn('Invalid date format:', dateString);
                return dateString;
            }

            const [, year, month, day, hour, minute, second] = match;

            // Build formatted date according to user's format
            let formattedDate = userDateFormat;

            // Replace PHP format characters with actual values
            formattedDate = formattedDate
                .replace(/d/g, day)
                .replace(/m/g, month)
                .replace(/Y/g, year)
                .replace(/y/g, year.slice(-2));

            // Add time in H:i:s format
            const timeString = `${hour}:${minute}:${second}`;

            return `${formattedDate} ${timeString}`;

        } catch (error) {
            console.error('Error formatting date:', error, 'Date string:', dateString);
            return dateString;
        }
    }

    /**
     * Get user preferences from window globals or API data
     * @param {object} documentData - Optional document data containing user preferences
     * @returns {object} - Object with timezone and dateFormat
     */
    static getUserPreferences(documentData = null) {
        let timezone = 'UTC';
        let dateFormat = 'Y-m-d';

        // First priority: document data from API
        if (documentData) {
            if (documentData.user_timezone) {
                timezone = documentData.user_timezone;
            }
            if (documentData.user_date_format) {
                dateFormat = documentData.user_date_format;
            }
        }

        // Second priority: window globals set by template
        if (window.userTimezone) {
            timezone = window.userTimezone;
        }
        if (window.userDateFormat) {
            dateFormat = window.userDateFormat;
        }

        return { timezone, dateFormat };
    }

    /**
     * Format a date using preferences from window globals or document data
     * @param {string} dateString - Date string to format
     * @param {object} documentData - Optional document data
     * @returns {string} - Formatted date string
     */
    static formatWithUserPreferences(dateString, documentData = null) {
        const { timezone, dateFormat } = this.getUserPreferences(documentData);
        return this.formatDate(dateString, timezone, dateFormat);
    }
}

// Make the class available globally
window.DocumentDetailDateFormatter = DocumentDetailDateFormatter;
