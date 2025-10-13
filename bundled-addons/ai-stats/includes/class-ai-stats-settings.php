<?php
/**
 * AI-Stats Settings Class
 *
 * Manages plugin settings
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings management class
 */
class AI_Stats_Settings {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Settings
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Settings
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Settings will be managed through admin interface
    }
    
    /**
     * Get setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public function get($key, $default = null) {
        $settings = get_option('ai_stats_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Set setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success status
     */
    public function set($key, $value) {
        $settings = get_option('ai_stats_settings', array());
        $settings[$key] = $value;
        return update_option('ai_stats_settings', $settings);
    }
    
    /**
     * Get all settings
     * 
     * @return array All settings
     */
    public function get_all() {
        return get_option('ai_stats_settings', array());
    }
    
    /**
     * Update multiple settings
     * 
     * @param array $new_settings Settings to update
     * @return bool Success status
     */
    public function update($new_settings) {
        $settings = get_option('ai_stats_settings', array());
        $settings = array_merge($settings, $new_settings);
        return update_option('ai_stats_settings', $settings);
    }
}

