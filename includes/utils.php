<?php
/**
 * Common utility functions for the application
 */

/**
 * Format date to DD-MM-YYYY format
 * @param string $date The date string to format
 * @return string Formatted date
 */
function formatDateDMY($date) {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y', $timestamp) : '';
}

/**
 * Format datetime to DD-MM-YYYY HH:MM format
 * @param string $date The datetime string to format
 * @return string Formatted datetime
 */
function formatDateTimeDMY($date) {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y H:i', $timestamp) : '';
}

/**
 * Convert date from DD-MM-YYYY format to YYYY-MM-DD for database storage
 * @param string $date The date string in DD-MM-YYYY format
 * @return string|null Date in YYYY-MM-DD format or null if invalid
 */
function formatDateForDB($date) {
    if (!$date) return null;
    
    // If date is already in YYYY-MM-DD format, return as is
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Try to parse DD-MM-YYYY format
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        
        // Validate date
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }
    
    // If all else fails, try strtotime
    $timestamp = strtotime($date);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

/**
 * Validate date format (DD-MM-YYYY)
 * @param string $date The date string to validate
 * @return bool True if valid date in DD-MM-YYYY format, false otherwise
 */
function isValidDate($date) {
    if (!$date) return false;
    
    // Check for DD-MM-YYYY format
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        return checkdate($month, $day, $year);
    }
    
    return false;
}
?>
