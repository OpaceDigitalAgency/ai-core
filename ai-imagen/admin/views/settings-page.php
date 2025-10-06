<?php
/**
 * AI-Imagen Settings Page
 * 
 * Plugin settings interface
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$settings = AI_Imagen_Settings::get_instance();
?>

<div class="wrap ai-imagen-settings">
    <h1><?php esc_html_e('AI-Imagen Settings', 'ai-imagen'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('ai_imagen_settings_group');
        do_settings_sections('ai-imagen-settings');
        submit_button();
        ?>
    </form>
</div>

