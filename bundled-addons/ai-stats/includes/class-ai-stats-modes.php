<?php
/**
 * AI-Stats Modes Class
 *
 * Manages the 6 content generation modes
 *
 * @package AI_Stats
 * @version 0.2.6
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modes management class
 */
class AI_Stats_Modes {
    
    /**
     * Get all available modes
     * 
     * @return array Available modes
     */
    public static function get_modes() {
        return array(
            'statistics' => array(
                'name' => __('Statistical Authority Injector', 'ai-stats'),
                'description' => __('Inject authoritative business statistics with citations', 'ai-stats'),
                'update_frequency' => 'daily',
                'icon' => 'dashicons-chart-bar',
            ),
            'birmingham' => array(
                'name' => __('Birmingham Business Stats', 'ai-stats'),
                'description' => __('Local Birmingham business statistics and data', 'ai-stats'),
                'update_frequency' => 'daily',
                'icon' => 'dashicons-location',
            ),
            'trends' => array(
                'name' => __('Industry Trend Micro-Module', 'ai-stats'),
                'description' => __('Latest SEO and web design industry trends', 'ai-stats'),
                'update_frequency' => 'daily',
                'icon' => 'dashicons-chart-line',
            ),
            'benefits' => array(
                'name' => __('Service + Benefit Semantic Expander', 'ai-stats'),
                'description' => __('Benefit-focused service descriptions', 'ai-stats'),
                'update_frequency' => 'weekly',
                'icon' => 'dashicons-star-filled',
            ),
            'seasonal' => array(
                'name' => __('Seasonal Service Angle Rotator', 'ai-stats'),
                'description' => __('Seasonal variations of service offerings', 'ai-stats'),
                'update_frequency' => 'monthly',
                'icon' => 'dashicons-calendar-alt',
            ),
            'process' => array(
                'name' => __('Service Process Micro-Step Enhancer', 'ai-stats'),
                'description' => __('Detailed process descriptions demonstrating expertise', 'ai-stats'),
                'update_frequency' => 'weekly',
                'icon' => 'dashicons-list-view',
            ),
        );
    }
    
    /**
     * Get mode details
     * 
     * @param string $mode_key Mode key
     * @return array|null Mode details or null
     */
    public static function get_mode($mode_key) {
        $modes = self::get_modes();
        return isset($modes[$mode_key]) ? $modes[$mode_key] : null;
    }
    
    /**
     * Check if mode exists
     * 
     * @param string $mode_key Mode key
     * @return bool True if mode exists
     */
    public static function mode_exists($mode_key) {
        $modes = self::get_modes();
        return isset($modes[$mode_key]);
    }
}

