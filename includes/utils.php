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
    return date('d-m-Y', strtotime($date));
}

/**
 * Format datetime to DD-MM-YYYY HH:MM format
 * @param string $date The datetime string to format
 * @return string Formatted datetime
 */
function formatDateTimeDMY($date) {
    if (!$date) return '';
    return date('d-m-Y H:i', strtotime($date));
}
?>
