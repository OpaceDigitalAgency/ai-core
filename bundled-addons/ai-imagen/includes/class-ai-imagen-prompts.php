<?php
/**
 * AI-Imagen Prompts Class
 * 
 * Manages prompt templates and library integration
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Imagen Prompts Class
 */
class AI_Imagen_Prompts {
    
    /**
     * Class instance
     * 
     * @var AI_Imagen_Prompts
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Imagen_Prompts
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
     * Install prompt templates to AI-Core Prompt Library
     *
     * @return void
     */
    public static function install_templates() {
        global $wpdb;

        // Get AI-Core prompt library tables
        $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
        $prompts_table = $wpdb->prefix . 'ai_core_prompts';

        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '{$groups_table}'") !== $groups_table) {
            return;
        }

        // Check if prompts already exist (avoid duplicates)
        $existing_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prompts_table} WHERE type = %s",
            'image'
        ));

        if ($existing_count > 0) {
            return; // Prompts already installed
        }

        // Create groups and prompts
        $templates = self::get_template_data();

        foreach ($templates as $group_name => $group_data) {
            // Check if group already exists
            $existing_group = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$groups_table} WHERE name = %s",
                $group_data['name']
            ));

            if ($existing_group) {
                $group_id = $existing_group;
            } else {
                // Create group
                $wpdb->insert(
                    $groups_table,
                    array(
                        'name' => $group_data['name'],
                        'description' => $group_data['description'],
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ),
                    array('%s', '%s', '%s', '%s')
                );

                $group_id = $wpdb->insert_id;
            }

            // Create prompts for this group
            if ($group_id && !empty($group_data['prompts'])) {
                foreach ($group_data['prompts'] as $prompt) {
                    $wpdb->insert(
                        $prompts_table,
                        array(
                            'group_id' => $group_id,
                            'title' => $prompt['title'],
                            'prompt' => $prompt['prompt'],
                            'provider' => isset($prompt['provider']) ? $prompt['provider'] : '',
                            'model' => isset($prompt['model']) ? $prompt['model'] : '',
                            'type' => 'image',
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ),
                        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                    );
                }
            }
        }
    }
    
    /**
     * Get template data
     * 
     * @return array Template data organized by groups
     */
    private static function get_template_data() {
        return array(
            'marketing-ads' => array(
                'name' => 'Marketing & Advertising',
                'description' => 'Professional marketing and advertising image templates',
                'prompts' => array(
                    array(
                        'title' => 'Product Hero Banner',
                        'prompt' => 'Professional product photography on clean white background, studio lighting, high-end commercial quality, sharp focus, 4K resolution',
                    ),
                    array(
                        'title' => 'Lifestyle Marketing Shot',
                        'prompt' => 'Lifestyle product photography in modern home setting, natural lighting, aspirational aesthetic, professional commercial quality',
                    ),
                    array(
                        'title' => 'Campaign Banner',
                        'prompt' => 'Eye-catching advertising banner with bold typography space, vibrant colours, modern design, professional marketing material',
                    ),
                    array(
                        'title' => 'A/B Test Variant',
                        'prompt' => 'Clean product showcase with minimalist background, professional lighting, commercial photography style, suitable for A/B testing',
                    ),
                ),
            ),
            'social-media' => array(
                'name' => 'Social Media Content',
                'description' => 'Engaging visuals optimised for social media platforms',
                'prompts' => array(
                    array(
                        'title' => 'Instagram Post',
                        'prompt' => 'Square format social media image, vibrant colours, eye-catching composition, Instagram-optimised aesthetic, modern and trendy',
                    ),
                    array(
                        'title' => 'Story Template',
                        'prompt' => 'Vertical 9:16 social media story background, bold colours, space for text overlay, engaging visual design',
                    ),
                    array(
                        'title' => 'Quote Card',
                        'prompt' => 'Inspirational quote card background, elegant design, soft colours, professional typography space, social media ready',
                    ),
                    array(
                        'title' => 'Thumbnail Image',
                        'prompt' => 'Attention-grabbing thumbnail image, bold composition, high contrast, optimised for small display sizes',
                    ),
                ),
            ),
            'product-photography' => array(
                'name' => 'Product Photography',
                'description' => 'Professional product photography templates',
                'prompts' => array(
                    array(
                        'title' => 'White Background Product',
                        'prompt' => 'Product on pure white background, professional studio lighting, e-commerce quality, sharp focus, clean shadows',
                    ),
                    array(
                        'title' => 'Lifestyle Context',
                        'prompt' => 'Product in lifestyle setting, natural environment, contextual usage, professional photography, authentic feel',
                    ),
                    array(
                        'title' => 'Detail Shot',
                        'prompt' => 'Close-up product detail shot, macro photography, high resolution, professional lighting, texture emphasis',
                    ),
                    array(
                        'title' => 'Floating Product',
                        'prompt' => 'Product floating in mid-air, creative composition, professional lighting, modern e-commerce style, clean background',
                    ),
                ),
            ),
            'website-design' => array(
                'name' => 'Website Design Elements',
                'description' => 'Modern web design graphics and illustrations',
                'prompts' => array(
                    array(
                        'title' => 'Hero Section Background',
                        'prompt' => 'Modern website hero section background, abstract geometric shapes, professional design, web-optimised colours',
                    ),
                    array(
                        'title' => 'Feature Illustration',
                        'prompt' => 'Clean vector-style illustration for website feature section, modern flat design, professional colour palette',
                    ),
                    array(
                        'title' => 'Icon Set Element',
                        'prompt' => 'Minimalist icon design, flat style, consistent line weight, professional UI design, web-optimised',
                    ),
                    array(
                        'title' => 'Background Pattern',
                        'prompt' => 'Subtle website background pattern, modern geometric design, professional aesthetic, non-distracting',
                    ),
                ),
            ),
            'publishing' => array(
                'name' => 'Publishing & Editorial',
                'description' => 'Editorial and publishing quality images',
                'prompts' => array(
                    array(
                        'title' => 'Article Header',
                        'prompt' => 'Editorial article header image, professional journalism quality, relevant visual metaphor, publication-ready',
                    ),
                    array(
                        'title' => 'Magazine Cover',
                        'prompt' => 'Magazine cover design, bold visual impact, professional photography style, space for text overlay',
                    ),
                    array(
                        'title' => 'Infographic Element',
                        'prompt' => 'Clean infographic visual element, data visualisation style, professional design, editorial quality',
                    ),
                    array(
                        'title' => 'Book Cover Art',
                        'prompt' => 'Book cover artwork, professional publishing quality, genre-appropriate aesthetic, print-ready design',
                    ),
                ),
            ),
            'presentations' => array(
                'name' => 'Presentation Graphics',
                'description' => 'Professional presentation and slide backgrounds',
                'prompts' => array(
                    array(
                        'title' => 'Slide Background',
                        'prompt' => 'Professional presentation slide background, corporate aesthetic, subtle design, space for content',
                    ),
                    array(
                        'title' => 'Data Visualisation',
                        'prompt' => 'Business data visualisation graphic, professional chart design, clear and informative, presentation-ready',
                    ),
                    array(
                        'title' => 'Concept Diagram',
                        'prompt' => 'Business concept diagram illustration, professional design, clear visual hierarchy, presentation quality',
                    ),
                    array(
                        'title' => 'Title Slide',
                        'prompt' => 'Presentation title slide background, professional corporate design, bold visual impact, branded aesthetic',
                    ),
                ),
            ),
            'game-development' => array(
                'name' => 'Game Development',
                'description' => 'Game art and concept design templates',
                'prompts' => array(
                    array(
                        'title' => 'Character Concept',
                        'prompt' => 'Game character concept art, professional game design style, detailed illustration, concept art quality',
                    ),
                    array(
                        'title' => 'Environment Art',
                        'prompt' => 'Game environment concept art, atmospheric design, professional game art style, detailed background',
                    ),
                    array(
                        'title' => 'UI Element',
                        'prompt' => 'Game UI element design, modern interface style, professional game design, clear and functional',
                    ),
                    array(
                        'title' => 'Asset Sprite',
                        'prompt' => 'Game asset sprite, clean design, professional game art style, suitable for 2D games',
                    ),
                ),
            ),
            'education' => array(
                'name' => 'Educational Content',
                'description' => 'Educational diagrams and learning materials',
                'prompts' => array(
                    array(
                        'title' => 'Educational Diagram',
                        'prompt' => 'Clear educational diagram, simple and informative design, professional teaching material, easy to understand',
                    ),
                    array(
                        'title' => 'Flashcard Visual',
                        'prompt' => 'Educational flashcard illustration, clear and simple design, learning-focused, child-friendly aesthetic',
                    ),
                    array(
                        'title' => 'Classroom Poster',
                        'prompt' => 'Educational classroom poster design, engaging visual, informative content space, professional teaching material',
                    ),
                    array(
                        'title' => 'Study Guide Graphic',
                        'prompt' => 'Study guide visual aid, clear and organised design, educational quality, student-friendly layout',
                    ),
                ),
            ),
            'print-on-demand' => array(
                'name' => 'Print-on-Demand',
                'description' => 'Print-ready designs for merchandise',
                'prompts' => array(
                    array(
                        'title' => 'T-Shirt Graphic',
                        'prompt' => 'T-shirt graphic design, bold and eye-catching, print-ready quality, transparent background, vector-style',
                    ),
                    array(
                        'title' => 'Sticker Design',
                        'prompt' => 'Sticker design with transparent background, bold colours, clean edges, print-ready quality, die-cut suitable',
                    ),
                    array(
                        'title' => 'Poster Art',
                        'prompt' => 'Poster artwork design, high resolution, bold visual impact, print-ready quality, professional design',
                    ),
                    array(
                        'title' => 'Mug Design',
                        'prompt' => 'Mug wrap design, 360-degree suitable, bold colours, print-ready quality, professional merchandise design',
                    ),
                ),
            ),
        );
    }
    
    /**
     * Get quick start ideas
     * 
     * @param string $category Category (use_case, role, or style)
     * @return array Quick start prompt ideas
     */
    public static function get_quick_start_ideas($category = '') {
        $ideas = array(
            'general' => array(
                'A professional product photo on white background',
                'Modern minimalist website hero image',
                'Vibrant social media post graphic',
                'Abstract background pattern for presentations',
            ),
            'marketing-ads' => array(
                'Eye-catching product banner for email campaign',
                'Lifestyle product shot in modern home setting',
                'Professional advertising banner with bold colours',
                'A/B test variant with clean product focus',
            ),
            'social-media' => array(
                'Instagram-ready square post with vibrant colours',
                'Vertical story background with text space',
                'Quote card with elegant design',
                'Attention-grabbing thumbnail for video',
            ),
            'photorealistic' => array(
                'DSLR quality product photography',
                'Cinematic lifestyle shot with natural lighting',
                'Macro detail shot with shallow depth of field',
                'Professional studio portrait lighting',
            ),
        );
        
        return isset($ideas[$category]) ? $ideas[$category] : $ideas['general'];
    }
}

