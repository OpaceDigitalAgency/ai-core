<?php
/**
 * AI-Pulse Settings Page
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-pulse-admin">
    <h1>
        <span class="ai-pulse-logo">🔮</span>
        AI-Pulse
        <span class="ai-pulse-version">v<?php echo esc_html(AI_PULSE_VERSION); ?></span>
    </h1>

    <?php settings_errors('ai_pulse'); ?>

    <nav class="nav-tab-wrapper">
        <a href="?page=ai-pulse&tab=test" class="nav-tab <?php echo $active_tab === 'test' ? 'nav-tab-active' : ''; ?>">
            Test Interface
        </a>
        <a href="?page=ai-pulse&tab=keywords" class="nav-tab <?php echo $active_tab === 'keywords' ? 'nav-tab-active' : ''; ?>">
            Keywords
        </a>
        <a href="?page=ai-pulse&tab=prompts" class="nav-tab <?php echo $active_tab === 'prompts' ? 'nav-tab-active' : ''; ?>">
            Prompts
        </a>
        <a href="?page=ai-pulse&tab=schedule" class="nav-tab <?php echo $active_tab === 'schedule' ? 'nav-tab-active' : ''; ?>">
            Scheduling
        </a>
        <a href="?page=ai-pulse&tab=library" class="nav-tab <?php echo $active_tab === 'library' ? 'nav-tab-active' : ''; ?>">
            Content Library
        </a>
        <a href="?page=ai-pulse&tab=stats" class="nav-tab <?php echo $active_tab === 'stats' ? 'nav-tab-active' : ''; ?>">
            Statistics
        </a>
        <a href="?page=ai-pulse&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            Settings
        </a>
    </nav>

    <div class="ai-pulse-tab-content">
        <?php
        switch ($active_tab) {
            case 'test':
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-test-interface.php';
                break;
            case 'keywords':
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-keywords.php';
                break;
            case 'prompts':
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-prompts.php';
                break;
            case 'schedule':
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-schedule.php';
                break;
            case 'library':
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-library.php';
                break;
            case 'stats':
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-stats.php';
                break;
            case 'settings':
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-settings.php';
                break;
            default:
                include AI_PULSE_PLUGIN_DIR . 'admin/views/tab-test-interface.php';
        }
        ?>
    </div>
</div>

