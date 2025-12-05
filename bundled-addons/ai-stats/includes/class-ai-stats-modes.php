<?php
/**
 * AI-Stats Modes Class
 *
 * Manages the 6 content generation modes
 *
 * @package AI_Stats
 * @version 0.8.1
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
                'name' => __('Statistics Generator', 'ai-stats'),
                'description' => __('Generate 3 data-driven bullet points with real statistics, emojis, and source citations', 'ai-stats'),
                'update_frequency' => 'daily',
                'icon' => 'dashicons-chart-bar',
                'enabled' => true,
            ),
            'news_summary' => array(
                'name' => __('Daily News Summary', 'ai-stats'),
                'description' => __('AI-generated summary of latest industry news and trends from shortlisted articles', 'ai-stats'),
                'update_frequency' => 'daily',
                'icon' => 'dashicons-megaphone',
                'enabled' => true,
            ),
            'birmingham' => array(
                'name' => __('Birmingham Business Stats', 'ai-stats'),
                'description' => __('Local Birmingham business statistics and data', 'ai-stats'),
                'update_frequency' => 'daily',
                'icon' => 'dashicons-location',
                'enabled' => false,
            ),
            'trends' => array(
                'name' => __('Industry Trend Micro-Module', 'ai-stats'),
                'description' => __('Latest SEO and web design industry trends', 'ai-stats'),
                'update_frequency' => 'daily',
                'icon' => 'dashicons-chart-line',
                'enabled' => false,
            ),
            'benefits' => array(
                'name' => __('Service + Benefit Semantic Expander', 'ai-stats'),
                'description' => __('Benefit-focused service descriptions', 'ai-stats'),
                'update_frequency' => 'weekly',
                'icon' => 'dashicons-star-filled',
                'enabled' => false,
            ),
            'seasonal' => array(
                'name' => __('Seasonal Service Angle Rotator', 'ai-stats'),
                'description' => __('Seasonal variations of service offerings', 'ai-stats'),
                'update_frequency' => 'monthly',
                'icon' => 'dashicons-calendar-alt',
                'enabled' => false,
            ),
            'process' => array(
                'name' => __('Service Process Micro-Step Enhancer', 'ai-stats'),
                'description' => __('Detailed process descriptions demonstrating expertise', 'ai-stats'),
                'update_frequency' => 'weekly',
                'icon' => 'dashicons-list-view',
                'enabled' => false,
            ),
        );
    }

    /**
     * Get only enabled modes
     *
     * @return array Enabled modes
     */
    public static function get_enabled_modes() {
        return array_filter(self::get_modes(), function($mode) {
            return isset($mode['enabled']) && $mode['enabled'] === true;
        });
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

