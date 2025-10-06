<?php
/**
 * AI-Imagen Settings Class
 * 
 * Manages plugin settings and preferences
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Imagen Settings Class
 */
class AI_Imagen_Settings {
    
    /**
     * Class instance
     * 
     * @var AI_Imagen_Settings
     */
    private static $instance = null;
    
    /**
     * Settings option name
     * 
     * @var string
     */
    private $option_name = 'ai_imagen_settings';
    
    /**
     * Settings group
     * 
     * @var string
     */
    private $settings_group = 'ai_imagen_settings_group';
    
    /**
     * Settings page slug
     * 
     * @var string
     */
    private $settings_page = 'ai-imagen-settings';
    
    /**
     * Get class instance
     * 
     * @return AI_Imagen_Settings
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
     * Initialize settings
     * 
     * @return void
     */
    private function init() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register plugin settings
     * 
     * @return void
     */
    public function register_settings() {
        // Register settings
        register_setting(
            $this->settings_group,
            $this->option_name,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_default_settings(),
                'show_in_rest' => false
            )
        );
        
        // Add settings sections
        $this->add_settings_sections();
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings sections
     * 
     * @return void
     */
    private function add_settings_sections() {
        // Default Settings Section
        add_settings_section(
            'ai_imagen_defaults_section',
            __('Default Generation Settings', 'ai-imagen'),
            array($this, 'defaults_section_callback'),
            $this->settings_page
        );
        
        // Limits Section
        add_settings_section(
            'ai_imagen_limits_section',
            __('Usage Limits', 'ai-imagen'),
            array($this, 'limits_section_callback'),
            $this->settings_page
        );
        
        // Features Section
        add_settings_section(
            'ai_imagen_features_section',
            __('Features', 'ai-imagen'),
            array($this, 'features_section_callback'),
            $this->settings_page
        );
    }
    
    /**
     * Add settings fields
     * 
     * @return void
     */
    private function add_settings_fields() {
        // Default Quality
        add_settings_field(
            'default_quality',
            __('Default Quality', 'ai-imagen'),
            array($this, 'quality_field_callback'),
            $this->settings_page,
            'ai_imagen_defaults_section'
        );
        
        // Default Format
        add_settings_field(
            'default_format',
            __('Default Format', 'ai-imagen'),
            array($this, 'format_field_callback'),
            $this->settings_page,
            'ai_imagen_defaults_section'
        );
        
        // Default Aspect Ratio
        add_settings_field(
            'default_aspect_ratio',
            __('Default Aspect Ratio', 'ai-imagen'),
            array($this, 'aspect_ratio_field_callback'),
            $this->settings_page,
            'ai_imagen_defaults_section'
        );
        
        // Default Background
        add_settings_field(
            'default_background',
            __('Default Background', 'ai-imagen'),
            array($this, 'background_field_callback'),
            $this->settings_page,
            'ai_imagen_defaults_section'
        );
        
        // Auto Save to Library
        add_settings_field(
            'auto_save_to_library',
            __('Auto Save to Media Library', 'ai-imagen'),
            array($this, 'auto_save_field_callback'),
            $this->settings_page,
            'ai_imagen_defaults_section'
        );
        
        // Generation Limit
        add_settings_field(
            'generation_limit',
            __('Daily Generation Limit', 'ai-imagen'),
            array($this, 'generation_limit_field_callback'),
            $this->settings_page,
            'ai_imagen_limits_section'
        );
        
        // Enable Scene Builder
        add_settings_field(
            'enable_scene_builder',
            __('Enable Scene Builder', 'ai-imagen'),
            array($this, 'scene_builder_field_callback'),
            $this->settings_page,
            'ai_imagen_features_section'
        );
        
        // Enable Prompt Enhancement
        add_settings_field(
            'enable_prompt_enhancement',
            __('Enable Prompt Enhancement', 'ai-imagen'),
            array($this, 'prompt_enhancement_field_callback'),
            $this->settings_page,
            'ai_imagen_features_section'
        );
    }
    
    /**
     * Defaults section callback
     * 
     * @return void
     */
    public function defaults_section_callback() {
        echo '<p>' . esc_html__('Configure default settings for image generation.', 'ai-imagen') . '</p>';
    }
    
    /**
     * Limits section callback
     * 
     * @return void
     */
    public function limits_section_callback() {
        echo '<p>' . esc_html__('Set usage limits to control API costs.', 'ai-imagen') . '</p>';
    }
    
    /**
     * Features section callback
     * 
     * @return void
     */
    public function features_section_callback() {
        echo '<p>' . esc_html__('Enable or disable plugin features.', 'ai-imagen') . '</p>';
    }
    
    /**
     * Quality field callback
     * 
     * @return void
     */
    public function quality_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['default_quality']) ? $settings['default_quality'] : 'standard';
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[default_quality]">
            <option value="standard" <?php selected($value, 'standard'); ?>><?php esc_html_e('Standard', 'ai-imagen'); ?></option>
            <option value="hd" <?php selected($value, 'hd'); ?>><?php esc_html_e('HD', 'ai-imagen'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Higher quality images cost more tokens.', 'ai-imagen'); ?></p>
        <?php
    }
    
    /**
     * Format field callback
     * 
     * @return void
     */
    public function format_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['default_format']) ? $settings['default_format'] : 'png';
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[default_format]">
            <option value="png" <?php selected($value, 'png'); ?>>PNG</option>
            <option value="jpeg" <?php selected($value, 'jpeg'); ?>>JPEG</option>
            <option value="webp" <?php selected($value, 'webp'); ?>>WebP</option>
        </select>
        <p class="description"><?php esc_html_e('Default image format for generated images.', 'ai-imagen'); ?></p>
        <?php
    }
    
    /**
     * Aspect ratio field callback
     * 
     * @return void
     */
    public function aspect_ratio_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['default_aspect_ratio']) ? $settings['default_aspect_ratio'] : '1:1';
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[default_aspect_ratio]">
            <option value="1:1" <?php selected($value, '1:1'); ?>>1:1 (Square)</option>
            <option value="4:3" <?php selected($value, '4:3'); ?>>4:3 (Landscape)</option>
            <option value="16:9" <?php selected($value, '16:9'); ?>>16:9 (Widescreen)</option>
            <option value="9:16" <?php selected($value, '9:16'); ?>>9:16 (Portrait)</option>
        </select>
        <p class="description"><?php esc_html_e('Default aspect ratio for generated images.', 'ai-imagen'); ?></p>
        <?php
    }
    
    /**
     * Background field callback
     * 
     * @return void
     */
    public function background_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['default_background']) ? $settings['default_background'] : 'opaque';
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[default_background]">
            <option value="opaque" <?php selected($value, 'opaque'); ?>><?php esc_html_e('Opaque', 'ai-imagen'); ?></option>
            <option value="transparent" <?php selected($value, 'transparent'); ?>><?php esc_html_e('Transparent', 'ai-imagen'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Default background type (transparent may not be supported by all providers).', 'ai-imagen'); ?></p>
        <?php
    }
    
    /**
     * Auto save field callback
     * 
     * @return void
     */
    public function auto_save_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['auto_save_to_library']) ? $settings['auto_save_to_library'] : true;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[auto_save_to_library]" value="1" <?php checked($value, true); ?>>
            <?php esc_html_e('Automatically save generated images to WordPress media library', 'ai-imagen'); ?>
        </label>
        <?php
    }
    
    /**
     * Generation limit field callback
     * 
     * @return void
     */
    public function generation_limit_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['generation_limit']) ? $settings['generation_limit'] : 0;
        ?>
        <input type="number" name="<?php echo esc_attr($this->option_name); ?>[generation_limit]" value="<?php echo esc_attr($value); ?>" min="0" class="regular-text">
        <p class="description"><?php esc_html_e('Maximum number of images that can be generated per day (0 = unlimited).', 'ai-imagen'); ?></p>
        <?php
    }
    
    /**
     * Scene builder field callback
     * 
     * @return void
     */
    public function scene_builder_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['enable_scene_builder']) ? $settings['enable_scene_builder'] : true;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[enable_scene_builder]" value="1" <?php checked($value, true); ?>>
            <?php esc_html_e('Enable scene builder for adding text, logos, and icons to images', 'ai-imagen'); ?>
        </label>
        <?php
    }
    
    /**
     * Prompt enhancement field callback
     * 
     * @return void
     */
    public function prompt_enhancement_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = isset($settings['enable_prompt_enhancement']) ? $settings['enable_prompt_enhancement'] : true;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[enable_prompt_enhancement]" value="1" <?php checked($value, true); ?>>
            <?php esc_html_e('Enable AI-powered prompt enhancement', 'ai-imagen'); ?>
        </label>
        <?php
    }
    
    /**
     * Get default settings
     * 
     * @return array Default settings
     */
    private function get_default_settings() {
        return array(
            'default_quality' => 'standard',
            'default_format' => 'png',
            'default_aspect_ratio' => '1:1',
            'default_background' => 'opaque',
            'auto_save_to_library' => true,
            'generation_limit' => 0,
            'enable_scene_builder' => true,
            'enable_prompt_enhancement' => true,
        );
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input Raw input values
     * @return array Sanitized values
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize quality
        $sanitized['default_quality'] = isset($input['default_quality']) && in_array($input['default_quality'], array('standard', 'hd'), true) 
            ? $input['default_quality'] 
            : 'standard';
        
        // Sanitize format
        $sanitized['default_format'] = isset($input['default_format']) && in_array($input['default_format'], array('png', 'jpeg', 'webp'), true)
            ? $input['default_format']
            : 'png';
        
        // Sanitize aspect ratio
        $sanitized['default_aspect_ratio'] = isset($input['default_aspect_ratio']) && in_array($input['default_aspect_ratio'], array('1:1', '4:3', '16:9', '9:16'), true)
            ? $input['default_aspect_ratio']
            : '1:1';
        
        // Sanitize background
        $sanitized['default_background'] = isset($input['default_background']) && in_array($input['default_background'], array('opaque', 'transparent'), true)
            ? $input['default_background']
            : 'opaque';
        
        // Sanitize checkboxes
        $sanitized['auto_save_to_library'] = isset($input['auto_save_to_library']) && $input['auto_save_to_library'] === '1';
        $sanitized['enable_scene_builder'] = isset($input['enable_scene_builder']) && $input['enable_scene_builder'] === '1';
        $sanitized['enable_prompt_enhancement'] = isset($input['enable_prompt_enhancement']) && $input['enable_prompt_enhancement'] === '1';
        
        // Sanitize generation limit
        $sanitized['generation_limit'] = isset($input['generation_limit']) ? absint($input['generation_limit']) : 0;
        
        return $sanitized;
    }
    
    /**
     * Get setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public function get($key, $default = null) {
        $settings = get_option($this->option_name, $this->get_default_settings());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}

