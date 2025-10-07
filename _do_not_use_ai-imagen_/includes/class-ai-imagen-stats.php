<?php
/**
 * AI-Imagen Statistics Class
 * 
 * Tracks image generation statistics
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Imagen Statistics Class
 */
class AI_Imagen_Stats {
    
    /**
     * Class instance
     * 
     * @var AI_Imagen_Stats
     */
    private static $instance = null;
    
    /**
     * Stats option name
     * 
     * @var string
     */
    private static $option_name = 'ai_imagen_stats';
    
    /**
     * Get class instance
     * 
     * @return AI_Imagen_Stats
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
        // Private constructor for singleton
    }
    
    /**
     * Track image generation
     * 
     * @param array $params Generation parameters
     * @param array $response API response
     * @return void
     */
    public static function track_generation($params, $response) {
        $stats = get_option(self::$option_name, array());
        
        // Initialize stats structure if needed
        if (empty($stats)) {
            $stats = array(
                'total_generations' => 0,
                'by_provider' => array(),
                'by_model' => array(),
                'by_use_case' => array(),
                'by_role' => array(),
                'by_style' => array(),
                'by_date' => array(),
            );
        }
        
        // Increment total
        $stats['total_generations']++;
        
        // Track by provider
        $provider = isset($params['provider']) ? $params['provider'] : 'unknown';
        if (!isset($stats['by_provider'][$provider])) {
            $stats['by_provider'][$provider] = 0;
        }
        $stats['by_provider'][$provider]++;
        
        // Track by model
        $model = isset($params['model']) ? $params['model'] : 'unknown';
        if (!isset($stats['by_model'][$model])) {
            $stats['by_model'][$model] = 0;
        }
        $stats['by_model'][$model]++;
        
        // Track by use case
        if (!empty($params['use_case'])) {
            if (!isset($stats['by_use_case'][$params['use_case']])) {
                $stats['by_use_case'][$params['use_case']] = 0;
            }
            $stats['by_use_case'][$params['use_case']]++;
        }
        
        // Track by role
        if (!empty($params['role'])) {
            if (!isset($stats['by_role'][$params['role']])) {
                $stats['by_role'][$params['role']] = 0;
            }
            $stats['by_role'][$params['role']]++;
        }
        
        // Track by style
        if (!empty($params['style'])) {
            if (!isset($stats['by_style'][$params['style']])) {
                $stats['by_style'][$params['style']] = 0;
            }
            $stats['by_style'][$params['style']]++;
        }
        
        // Track by date
        $date = date('Y-m-d');
        if (!isset($stats['by_date'][$date])) {
            $stats['by_date'][$date] = 0;
        }
        $stats['by_date'][$date]++;
        
        // Save stats
        update_option(self::$option_name, $stats);
    }
    
    /**
     * Get all statistics
     * 
     * @return array Statistics data
     */
    public static function get_stats() {
        return get_option(self::$option_name, array());
    }
    
    /**
     * Get statistics summary
     * 
     * @return array Summary statistics
     */
    public static function get_summary() {
        $stats = self::get_stats();
        
        return array(
            'total_generations' => isset($stats['total_generations']) ? $stats['total_generations'] : 0,
            'today_generations' => self::get_today_count($stats),
            'this_week_generations' => self::get_week_count($stats),
            'this_month_generations' => self::get_month_count($stats),
            'most_used_provider' => self::get_most_used($stats, 'by_provider'),
            'most_used_model' => self::get_most_used($stats, 'by_model'),
            'most_used_use_case' => self::get_most_used($stats, 'by_use_case'),
            'most_used_style' => self::get_most_used($stats, 'by_style'),
        );
    }
    
    /**
     * Get today's generation count
     * 
     * @param array $stats Statistics data
     * @return int Today's count
     */
    private static function get_today_count($stats) {
        $today = date('Y-m-d');
        return isset($stats['by_date'][$today]) ? $stats['by_date'][$today] : 0;
    }
    
    /**
     * Get this week's generation count
     * 
     * @param array $stats Statistics data
     * @return int Week's count
     */
    private static function get_week_count($stats) {
        if (empty($stats['by_date'])) {
            return 0;
        }
        
        $week_start = strtotime('monday this week');
        $count = 0;
        
        foreach ($stats['by_date'] as $date => $date_count) {
            if (strtotime($date) >= $week_start) {
                $count += $date_count;
            }
        }
        
        return $count;
    }
    
    /**
     * Get this month's generation count
     * 
     * @param array $stats Statistics data
     * @return int Month's count
     */
    private static function get_month_count($stats) {
        if (empty($stats['by_date'])) {
            return 0;
        }
        
        $month_start = strtotime('first day of this month');
        $count = 0;
        
        foreach ($stats['by_date'] as $date => $date_count) {
            if (strtotime($date) >= $month_start) {
                $count += $date_count;
            }
        }
        
        return $count;
    }
    
    /**
     * Get most used item from category
     * 
     * @param array $stats Statistics data
     * @param string $category Category key
     * @return string Most used item
     */
    private static function get_most_used($stats, $category) {
        if (empty($stats[$category])) {
            return 'N/A';
        }
        
        arsort($stats[$category]);
        $keys = array_keys($stats[$category]);
        
        return $keys[0];
    }
    
    /**
     * Reset statistics
     * 
     * @return bool True on success
     */
    public static function reset_stats() {
        return delete_option(self::$option_name);
    }
    
    /**
     * Get chart data for date range
     * 
     * @param int $days Number of days to include
     * @return array Chart data
     */
    public static function get_chart_data($days = 30) {
        $stats = self::get_stats();
        
        if (empty($stats['by_date'])) {
            return array();
        }
        
        $chart_data = array();
        $end_date = time();
        $start_date = strtotime("-{$days} days");
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = isset($stats['by_date'][$date]) ? $stats['by_date'][$date] : 0;
            
            $chart_data[] = array(
                'date' => $date,
                'count' => $count,
            );
        }
        
        return array_reverse($chart_data);
    }
    
    /**
     * Export statistics as CSV
     * 
     * @return string CSV data
     */
    public static function export_csv() {
        $stats = self::get_stats();
        
        $csv = "Category,Item,Count\n";
        
        // Export by provider
        if (!empty($stats['by_provider'])) {
            foreach ($stats['by_provider'] as $provider => $count) {
                $csv .= "Provider," . esc_html($provider) . "," . $count . "\n";
            }
        }
        
        // Export by model
        if (!empty($stats['by_model'])) {
            foreach ($stats['by_model'] as $model => $count) {
                $csv .= "Model," . esc_html($model) . "," . $count . "\n";
            }
        }
        
        // Export by use case
        if (!empty($stats['by_use_case'])) {
            foreach ($stats['by_use_case'] as $use_case => $count) {
                $csv .= "Use Case," . esc_html($use_case) . "," . $count . "\n";
            }
        }
        
        // Export by date
        if (!empty($stats['by_date'])) {
            foreach ($stats['by_date'] as $date => $count) {
                $csv .= "Date," . esc_html($date) . "," . $count . "\n";
            }
        }
        
        return $csv;
    }
}

