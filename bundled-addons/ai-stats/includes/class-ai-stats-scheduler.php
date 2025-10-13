<?php
/**
 * AI-Stats Scheduler Class
 *
 * Manages WP Cron scheduling for automated content updates
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scheduler class
 */
class AI_Stats_Scheduler {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Scheduler
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Scheduler
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
        add_action('ai_stats_daily_update', array($this, 'run_daily_update'));
        add_action('ai_stats_weekly_update', array($this, 'run_weekly_update'));
    }
    
    /**
     * Run daily update
     *
     * NOTE: Disabled for manual workflow mode
     * Auto-updates are disabled to allow full manual control
     *
     * @return void
     */
    public function run_daily_update() {
        // Short-circuit: Manual workflow mode - no automatic updates
        return;

        $settings = get_option('ai_stats_settings', array());

        if (empty($settings['auto_update'])) {
            return;
        }
        
        $active_mode = $settings['active_mode'] ?? 'statistics';
        
        // Generate new content
        $generator = AI_Stats_Generator::get_instance();
        $result = $generator->generate_content($active_mode);
        
        if (!is_wp_error($result)) {
            // Deactivate old content
            AI_Stats_Database::deactivate_old_content($active_mode);
            
            // Store new content
            AI_Stats_Database::store_content(
                $active_mode,
                'module',
                $result['content'],
                $result['metadata'],
                $result['sources']
            );
        }
    }
    
    /**
     * Run weekly update
     * 
     * @return void
     */
    public function run_weekly_update() {
        // Clear expired cache
        AI_Stats_Database::clear_expired_cache();
    }
}

