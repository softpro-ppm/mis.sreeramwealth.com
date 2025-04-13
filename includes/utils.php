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
 * Convert date from any format to YYYY-MM-DD for database storage
 * @param string $date The date string to format
 * @return string Formatted date for database
 */
function formatDateForDB($date) {
    if (!$date) return null;
    $timestamp = strtotime($date);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

/**
 * Validate date format
 * @param string $date The date string to validate
 * @return bool True if valid date, false otherwise
 */
function isValidDate($date) {
    if (!$date) return false;
    $timestamp = strtotime($date);
    return $timestamp !== false && $timestamp !== -1;
}
?>
