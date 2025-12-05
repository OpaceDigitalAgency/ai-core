<?php
/**
 * AI-Pulse Settings Class
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings management class
 */
class AI_Pulse_Settings {

    /**
     * Settings option name
     */
    const OPTION_NAME = 'ai_pulse_settings';

    /**
     * Get all settings
     *
     * @return array
     */
    public static function get_all() {
        $defaults = self::get_defaults();
        $settings = get_option(self::OPTION_NAME, array());
        
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get a specific setting
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get($key, $default = null) {
        $settings = self::get_all();
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        return $default;
    }

    /**
     * Set a specific setting
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    public static function set($key, $value) {
        $settings = self::get_all();
        $settings[$key] = $value;
        
        return update_option(self::OPTION_NAME, $settings);
    }

    /**
     * Set multiple settings
     *
     * @param array $data Settings array
     * @return bool
     */
    public static function set_multiple($data) {
        $settings = self::get_all();
        $settings = array_merge($settings, $data);
        
        return update_option(self::OPTION_NAME, $settings);
    }

    /**
     * Set default settings (called on activation)
     *
     * @return bool
     */
    public static function set_defaults() {
        $existing = get_option(self::OPTION_NAME);
        
        // Don't override existing settings
        if ($existing !== false) {
            return false;
        }
        
        return add_option(self::OPTION_NAME, self::get_defaults());
    }

    /**
     * Get default settings
     *
     * @return array
     */
    public static function get_defaults() {
        return array(
            'update_interval' => 'daily',
            'start_time' => '03:00',
            'gradual_rollout_enabled' => true,
            'rollout_window_hours' => 2,
            'delay_between_requests' => 2,
            'max_concurrent_generations' => 3,
            'default_period' => 'weekly',
            'default_location' => 'Birmingham, West Midlands, UK',
            'keywords' => array(),
            'pause_on_error' => true,
            'max_errors' => 3,
            'email_notifications' => true,
            'notification_email' => get_option('admin_email'),
            'cache_duration' => 24, // hours
            'enable_debug' => false,
        );
    }

    /**
     * Delete all settings
     *
     * @return bool
     */
    public static function delete_all() {
        return delete_option(self::OPTION_NAME);
    }

    /**
     * Get keywords configuration
     *
     * @return array
     */
    public static function get_keywords() {
        return self::get('keywords', array());
    }

    /**
     * Add or update a keyword
     *
     * @param string $keyword Keyword
     * @param array $config Keyword configuration
     * @return bool
     */
    public static function save_keyword($keyword, $config) {
        $keywords = self::get_keywords();
        $keywords[$keyword] = $config;
        
        return self::set('keywords', $keywords);
    }

    /**
     * Delete a keyword
     *
     * @param string $keyword Keyword to delete
     * @return bool
     */
    public static function delete_keyword($keyword) {
        $keywords = self::get_keywords();
        
        if (isset($keywords[$keyword])) {
            unset($keywords[$keyword]);
            return self::set('keywords', $keywords);
        }
        
        return false;
    }
}

