<?php
/**
 * AI-Pulse Scheduler
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP Cron scheduling class
 */
class AI_Pulse_Scheduler {

    /**
     * Initialise scheduler
     */
    public static function init() {
        add_action('ai_pulse_scheduled_generation', array(__CLASS__, 'run_scheduled_generation'));
        add_filter('cron_schedules', array(__CLASS__, 'register_cron_schedules'));
    }

    /**
     * Register custom cron schedules
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public static function register_cron_schedules($schedules) {
        // Add 2-day interval
        $schedules['two_days'] = array(
            'interval' => 172800,  // 2 days in seconds
            'display' => __('Every 2 Days', 'ai-pulse')
        );

        // Add 3-day interval
        $schedules['three_days'] = array(
            'interval' => 259200,  // 3 days in seconds
            'display' => __('Every 3 Days', 'ai-pulse')
        );

        return $schedules;
    }

    /**
     * Schedule generation
     */
    public static function schedule() {
        $interval = AI_Pulse_Settings::get('update_interval', 'daily');
        $start_time = AI_Pulse_Settings::get('start_time', '03:00');

        // Clear existing schedule
        wp_clear_scheduled_hook('ai_pulse_scheduled_generation');

        // Calculate next run time
        $next_run = strtotime('today ' . $start_time);
        if ($next_run < time()) {
            $next_run = strtotime('tomorrow ' . $start_time);
        }

        // Schedule event
        wp_schedule_event($next_run, $interval, 'ai_pulse_scheduled_generation');

        AI_Pulse_Logger::log('Scheduled generation: ' . date('Y-m-d H:i:s', $next_run), AI_Pulse_Logger::LOG_LEVEL_INFO);
    }

    /**
     * Unschedule generation
     */
    public static function unschedule() {
        wp_clear_scheduled_hook('ai_pulse_scheduled_generation');
        AI_Pulse_Logger::log('Unscheduled generation', AI_Pulse_Logger::LOG_LEVEL_INFO);
    }

    /**
     * Run scheduled generation
     */
    public static function run_scheduled_generation() {
        AI_Pulse_Logger::log('Starting scheduled generation', AI_Pulse_Logger::LOG_LEVEL_INFO);

        $keywords = AI_Pulse_Settings::get_keywords();
        
        if (empty($keywords)) {
            AI_Pulse_Logger::log('No keywords configured', AI_Pulse_Logger::LOG_LEVEL_WARNING);
            return;
        }

        $gradual_rollout = AI_Pulse_Settings::get('gradual_rollout_enabled', true);
        $delay = AI_Pulse_Settings::get('delay_between_requests', 2);

        $generator = new AI_Pulse_Generator();
        $error_count = 0;
        $max_errors = AI_Pulse_Settings::get('max_errors', 3);

        foreach ($keywords as $keyword_data) {
            $keyword = $keyword_data['keyword'];
            $modes = isset($keyword_data['modes']) ? $keyword_data['modes'] : array('SUMMARY');
            $period = isset($keyword_data['period']) ? $keyword_data['period'] : AI_Pulse_Settings::get('default_period', 'weekly');

            foreach ($modes as $mode) {
                // Check error threshold
                if ($error_count >= $max_errors && AI_Pulse_Settings::get('pause_on_error', true)) {
                    AI_Pulse_Logger::log('Max errors reached, stopping generation', AI_Pulse_Logger::LOG_LEVEL_ERROR);
                    self::send_error_notification($error_count);
                    return;
                }

                // Generate content
                $result = $generator->generate_content($keyword, $mode, $period);

                if (is_wp_error($result)) {
                    $error_count++;
                    AI_Pulse_Logger::log(
                        'Generation failed: ' . $result->get_error_message(),
                        AI_Pulse_Logger::LOG_LEVEL_ERROR,
                        array('keyword' => $keyword, 'mode' => $mode)
                    );
                    continue;
                }

                // Store content
                AI_Pulse_Database::store_content($keyword, $mode, $period, $result);

                AI_Pulse_Logger::log(
                    'Generated content',
                    AI_Pulse_Logger::LOG_LEVEL_INFO,
                    array('keyword' => $keyword, 'mode' => $mode, 'tokens' => $result['input_tokens'] + $result['output_tokens'])
                );

                // Gradual rollout delay
                if ($gradual_rollout && $delay > 0) {
                    sleep($delay);
                }
            }
        }

        AI_Pulse_Logger::log('Scheduled generation completed', AI_Pulse_Logger::LOG_LEVEL_INFO);
    }

    /**
     * Send error notification email
     *
     * @param int $error_count Number of errors
     */
    private static function send_error_notification($error_count) {
        if (!AI_Pulse_Settings::get('email_notifications', true)) {
            return;
        }

        $to = AI_Pulse_Settings::get('notification_email', get_option('admin_email'));
        $subject = 'AI-Pulse: Scheduled Generation Errors';
        $message = "AI-Pulse encountered {$error_count} errors during scheduled generation.\n\n";
        $message .= "Please check the logs in the AI-Pulse admin panel.\n\n";
        $message .= "Site: " . get_bloginfo('name') . "\n";
        $message .= "Time: " . current_time('mysql');

        wp_mail($to, $subject, $message);
    }
}

