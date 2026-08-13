<?php
/**
 * AI-Core Admin Class
 * 
 * Handles admin interface and menu pages
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core Admin Class
 * 
 * Manages admin pages and interface
 */
class AI_Core_Admin {
    
    /**
     * Class instance
     * 
     * @var AI_Core_Admin
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Admin
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
        $this->init();
    }
    
    /**
     * Initialize admin
     *
     * @return void
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_head', array($this, 'print_theme_boot'));
    }

    /**
     * Apply the dark/light theme before first paint on AI-Core admin screens.
     *
     * AI-Scribe stores the visitor's choice under the `ai-scribe-theme` key and
     * flags it on the document element. AI-Core reads the same key so a site
     * running both plugins does not flip between a dark screen and a light one,
     * and falls back to the operating system preference when nothing is stored.
     *
     * @return void
     */
    public function print_theme_boot() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $id = $screen ? (string) $screen->id : '';

        if (strpos($id, 'ai-core') === false) {
            return;
        }

        $boot = "try{var t=window.localStorage.getItem('ai-scribe-theme');"
            . "if(!t&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){t='dark';}"
            . "if(t){document.documentElement.setAttribute('data-theme',t);"
            . "document.documentElement.setAttribute('data-ai-scribe-theme',t);}}catch(e){}";

        // Printed directly rather than through wp_print_inline_script_tag(),
        // which needs WordPress 5.7 while this plugin supports 5.0. The theme
        // has to be applied before first paint, so it cannot wait for the
        // enqueued admin script in the footer.
        echo '<script id="ai-core-theme-boot">' . $boot . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $boot is a fixed string literal assembled above from no dynamic input; escaping it would break the script.
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('AI-Core', 'ai-core'),
            __('AI-Core', 'ai-core'),
            'manage_options',
            'ai-core',
            array($this, 'render_dashboard_page'),
            'dashicons-admin-generic',
            30
        );
        
        // Dashboard submenu (same as main)
        add_submenu_page(
            'ai-core',
            __('Dashboard', 'ai-core'),
            __('Dashboard', 'ai-core'),
            'manage_options',
            'ai-core',
            array($this, 'render_dashboard_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'ai-core',
            __('Settings', 'ai-core'),
            __('Settings', 'ai-core'),
            'manage_options',
            'ai-core-settings',
            array($this, 'render_settings_page')
        );

        // Prompt Library submenu
        add_submenu_page(
            'ai-core',
            __('Prompt Library', 'ai-core'),
            __('Prompt Library', 'ai-core'),
            'manage_options',
            'ai-core-prompt-library',
            array($this, 'render_prompt_library_page')
        );

        // Statistics submenu
        add_submenu_page(
            'ai-core',
            __('Statistics', 'ai-core'),
            __('Statistics', 'ai-core'),
            'manage_options',
            'ai-core-stats',
            array($this, 'render_stats_page')
        );

        // Add-ons submenu
        add_submenu_page(
            'ai-core',
            __('Add-ons', 'ai-core'),
            __('Add-ons', 'ai-core'),
            'manage_options',
            'ai-core-addons',
            array($this, 'render_addons_page')
        );
    }
    
    /**
     * Render dashboard page
     *
     * @return void
     */
    public function render_dashboard_page() {
        $api = AI_Core_API::get_instance();
        $configured = $api->is_configured();
        $providers = $api->get_configured_providers();
        $stats = AI_Core_Stats::get_instance()->get_total_stats();

        // Quick Stats are read defensively: a site with no recorded usage has no
        // counters at all, and an older stats option predates the total_tokens key.
        $total_requests = isset($stats['requests']) ? (int) $stats['requests'] : 0;
        $total_tokens   = isset($stats['total_tokens']) ? (int) $stats['total_tokens'] : (isset($stats['tokens']) ? (int) $stats['tokens'] : 0);
        $models_used    = isset($stats['models_used']) ? (int) $stats['models_used'] : 0;
        $has_usage      = ($total_requests > 0 || $total_tokens > 0 || $models_used > 0);

        ?>
        <div class="wrap ai-core-dashboard">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-core-welcome-panel">
                <h2><?php esc_html_e('Welcome to AI-Core', 'ai-core'); ?></h2>
                <p><?php esc_html_e('Universal AI Integration Hub for WordPress', 'ai-core'); ?></p>
                
                <?php if (!$configured): ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong><?php esc_html_e('Getting Started:', 'ai-core'); ?></strong>
                            <?php esc_html_e('Please configure at least one API key in the Settings page to start using AI-Core.', 'ai-core'); ?>
                        </p>
                        <p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>" class="button button-primary">
                                <?php esc_html_e('Configure API Keys', 'ai-core'); ?>
                            </a>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-success inline">
                        <p>
                            <strong><?php esc_html_e('Status:', 'ai-core'); ?></strong>
                            <?php
                            printf(
                                /* translators: %d: number of AI providers that have an API key configured. */
                                esc_html(_n('%d provider configured', '%d providers configured', count($providers), 'ai-core')),
                                (int) count($providers)
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($configured): ?>
                <div class="ai-core-stats-overview">
                    <h2><?php esc_html_e('Quick Stats', 'ai-core'); ?></h2>
                    <div class="ai-core-stats-grid">
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Total Requests', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo esc_html(number_format_i18n($total_requests)); ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Total Tokens', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo esc_html(number_format_i18n($total_tokens)); ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Configured Providers', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo esc_html(number_format_i18n(count($providers))); ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Models Used', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo esc_html(number_format_i18n($models_used)); ?></span>
                        </div>
                    </div>
                    <?php if (!$has_usage): ?>
                        <p class="ai-core-stats-hint">
                            <?php esc_html_e('No requests recorded yet. Counters start moving as soon as AI-Core or one of its add-ons sends its first request.', 'ai-core'); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="ai-core-providers-status">
                    <h2><?php esc_html_e('Configured Providers', 'ai-core'); ?></h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Provider', 'ai-core'); ?></th>
                                <th><?php esc_html_e('Status', 'ai-core'); ?></th>
                                <th><?php esc_html_e('Available Models', 'ai-core'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $provider): 
                                $models = $api->get_available_models($provider);
                                $provider_names = array(
                                    'openai' => 'OpenAI',
                                    'anthropic' => 'Anthropic Claude',
                                    'gemini' => 'Google Gemini',
                                    // xAI Grok is withheld — see ModelRegistry::getSupportedProviders().
                                );
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($provider_names[$provider] ?? $provider); ?></strong></td>
                                    <td><span class="dashicons dashicons-yes-alt ai-core-status-ok"></span> <?php esc_html_e('Configured', 'ai-core'); ?></td>
                                    <td><?php echo count($models); ?> <?php esc_html_e('models', 'ai-core'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="ai-core-quick-links">
                <h2><?php esc_html_e('Quick Links', 'ai-core'); ?></h2>
                <div class="ai-core-links-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>" class="ai-core-link-box">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <h3><?php esc_html_e('Settings', 'ai-core'); ?></h3>
                        <p><?php esc_html_e('Configure API keys and preferences', 'ai-core'); ?></p>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-stats')); ?>" class="ai-core-link-box">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h3><?php esc_html_e('Statistics', 'ai-core'); ?></h3>
                        <p><?php esc_html_e('View detailed usage statistics', 'ai-core'); ?></p>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-addons')); ?>" class="ai-core-link-box">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <h3><?php esc_html_e('Add-ons', 'ai-core'); ?></h3>
                        <p><?php esc_html_e('Discover plugins that extend AI-Core', 'ai-core'); ?></p>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('ai_core_settings'); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_core_settings_group');
                do_settings_sections('ai-core-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render statistics page
     *
     * @return void
     */
    public function render_stats_page() {
        $stats = AI_Core_Stats::get_instance();
        $total = $stats->get_total_stats();

        // Nothing to reset until at least one counter has moved, so the control
        // is withheld rather than offered as a no-op.
        $has_usage = (
            (isset($total['requests']) ? (int) $total['requests'] : 0) > 0
            || (isset($total['total_tokens']) ? (int) $total['total_tokens'] : 0) > 0
            || (isset($total['models_used']) ? (int) $total['models_used'] : 0) > 0
            || (isset($total['tools_used']) ? (int) $total['tools_used'] : 0) > 0
            || (isset($total['errors']) ? (int) $total['errors'] : 0) > 0
        );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="ai-core-stats-page">
                <h2 class="screen-reader-text"><?php esc_html_e('Usage summary', 'ai-core'); ?></h2>

                <?php
                // format_stats_html() builds its own table markup; wp_kses_post()
                // keeps the tables and spans it needs while stripping anything else.
                echo wp_kses_post($stats->format_stats_html());
                ?>

                <?php if ($has_usage): ?>
                    <p>
                        <button type="button" class="button button-primary" id="ai-core-refresh-pricing">
                            <?php esc_html_e('Refresh Model Pricing', 'ai-core'); ?>
                        </button>
                        <button type="button" class="button" id="ai-core-reset-stats">
                            <?php esc_html_e('Reset Statistics', 'ai-core'); ?>
                        </button>
                    </p>
                    <div id="ai-core-pricing-status" class="ai-core-inline-status" role="status" aria-live="polite"></div>
                <?php else: ?>
                    <p class="ai-core-stats-hint">
                        <?php esc_html_e('There is nothing to reset yet. Once usage is recorded, a Reset Statistics button appears here.', 'ai-core'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render prompt library page
     *
     * @return void
     */
    public function render_prompt_library_page() {
        $library = AI_Core_Prompt_Library::get_instance();
        $library->render_page();
    }

    /**
     * Render add-ons page
     *
     * @return void
     */
    public function render_addons_page() {
        $addons = AI_Core_Addons::get_instance();
        $addons->render_addons_page();
    }
}
