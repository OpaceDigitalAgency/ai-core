<?php
/**
 * AI-Pulse Logger
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logging class
 */
class AI_Pulse_Logger {

    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_DEBUG = 'debug';

    /**
     * Log a message
     *
     * @param string $message Log message
     * @param string $level Log level
     * @param array $context Additional context
     */
    public static function log($message, $level = self::LOG_LEVEL_INFO, $context = array()) {
        // Only log if debug is enabled or it's an error
        if (!AI_Pulse_Settings::get('enable_debug') && $level !== self::LOG_LEVEL_ERROR) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context
        );

        // Store in WordPress options (last 100 entries)
        $logs = get_option('ai_pulse_logs', array());
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 100);
        update_option('ai_pulse_logs', $logs);

        // Also log to error_log for errors
        if ($level === self::LOG_LEVEL_ERROR) {
            error_log('AI-Pulse Error: ' . $message . ' | Context: ' . json_encode($context));
        }
    }

    /**
     * Get recent logs
     *
     * @param int $limit Number of logs to retrieve
     * @return array Logs
     */
    public static function get_logs($limit = 50) {
        $logs = get_option('ai_pulse_logs', array());
        return array_slice($logs, 0, $limit);
    }

    /**
     * Clear all logs
     */
    public static function clear_logs() {
        delete_option('ai_pulse_logs');
    }
}

