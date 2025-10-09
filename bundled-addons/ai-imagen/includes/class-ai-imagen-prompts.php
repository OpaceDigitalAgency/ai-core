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
     * Clean up duplicate groups
     *
     * @return void
     */
    public static function cleanup_duplicate_groups() {
        global $wpdb;
        $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
        $prompts_table = $wpdb->prefix . 'ai_core_prompts';

        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '{$groups_table}'") !== $groups_table) {
            return;
        }

        // Find duplicate group names
        $duplicates = $wpdb->get_results("
            SELECT name, COUNT(*) as count, MIN(id) as keep_id
            FROM {$groups_table}
            GROUP BY name
            HAVING count > 1
        ", ARRAY_A);

        foreach ($duplicates as $duplicate) {
            $name = $duplicate['name'];
            $keep_id = $duplicate['keep_id'];

            // Get all IDs for this group name
            $all_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$groups_table} WHERE name = %s ORDER BY id ASC",
                $name
            ));

            // Remove the ID we want to keep
            $delete_ids = array_diff($all_ids, array($keep_id));

            if (!empty($delete_ids)) {
                // Update prompts to point to the kept group
                $delete_ids_str = implode(',', array_map('intval', $delete_ids));
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$prompts_table} SET group_id = %d WHERE group_id IN ({$delete_ids_str})",
                    $keep_id
                ));

                // Delete duplicate groups
                $wpdb->query("DELETE FROM {$groups_table} WHERE id IN ({$delete_ids_str})");
            }
        }
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

        // Clean up any duplicate groups first
        self::cleanup_duplicate_groups();

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

                // Update group description
                $wpdb->update(
                    $groups_table,
                    array(
                        'description' => $group_data['description'],
                        'updated_at' => current_time('mysql'),
                    ),
                    array('id' => $group_id),
                    array('%s', '%s'),
                    array('%d')
                );
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

            // Add prompts for this group (check for duplicates by title)
            if ($group_id && !empty($group_data['prompts'])) {
                foreach ($group_data['prompts'] as $prompt) {
                    // Check if prompt with this title already exists in this group
                    $existing_prompt = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$prompts_table} WHERE group_id = %d AND title = %s AND type = %s",
                        $group_id,
                        $prompt['title'],
                        'image'
                    ));

                    // Only insert if it doesn't exist
                    if (!$existing_prompt) {
                        $wpdb->insert(
                            $prompts_table,
                            array(
                                'group_id' => $group_id,
                                'title' => $prompt['title'],
                                'content' => $prompt['prompt'],
                                'provider' => isset($prompt['provider']) ? $prompt['provider'] : '',
                                'type' => 'image',
                                'created_at' => current_time('mysql'),
                                'updated_at' => current_time('mysql'),
                            ),
                            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
                        );
                    }
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
            // USE CASES (9 categories)
            'marketing-ads' => array(
                'name' => 'AI-Imagen: Marketing & Ads',
                'description' => 'Campaign banners, product shots',
                'prompts' => array(
                    array(
                        'title' => 'Campaign Banner',
                        'prompt' => 'Professional marketing campaign banner, bold call-to-action, eye-catching design, suitable for digital advertising, high conversion focus',
                    ),
                    array(
                        'title' => 'Product Shot Ad',
                        'prompt' => 'Commercial product shot for advertising, studio lighting, white background, professional e-commerce style, sharp focus, clean composition',
                    ),
                    array(
                        'title' => 'Brand Campaign Visual',
                        'prompt' => 'Modern brand campaign visual with bold typography, vibrant colours, professional corporate style, suitable for multi-channel marketing',
                    ),
                    array(
                        'title' => 'Promotional Banner',
                        'prompt' => 'Eye-catching promotional banner, sale-focused, bright and engaging, clear value proposition, suitable for online advertising',
                    ),
                    array(
                        'title' => 'Display Ad Creative',
                        'prompt' => 'High-impact display ad creative, attention-grabbing, optimised for digital platforms, professional advertising aesthetic',
                    ),
                ),
            ),
            'social-media' => array(
                'name' => 'AI-Imagen: Social Media',
                'description' => 'Posts, stories, thumbnails',
                'prompts' => array(
                    array(
                        'title' => 'Instagram Post',
                        'prompt' => 'Square format Instagram post, vibrant colours, eye-catching composition, modern aesthetic, optimised for engagement',
                    ),
                    array(
                        'title' => 'Story Background',
                        'prompt' => 'Vertical 9:16 story background, bold design, space for text overlay, attention-grabbing, mobile-optimised',
                    ),
                    array(
                        'title' => 'Social Thumbnail',
                        'prompt' => 'Attention-grabbing social media thumbnail, high contrast, bold composition, optimised for small display sizes',
                    ),
                    array(
                        'title' => 'Quote Card',
                        'prompt' => 'Inspirational quote card background, elegant design, soft colours, professional typography space, shareable',
                    ),
                    array(
                        'title' => 'Carousel Slide',
                        'prompt' => 'Social media carousel slide design, consistent branding, clear visual hierarchy, suitable for multi-slide posts',
                    ),
                ),
            ),
            'product-photography' => array(
                'name' => 'AI-Imagen: Product Photography',
                'description' => 'Professional product images',
                'prompts' => array(
                    array(
                        'title' => 'White Background Product',
                        'prompt' => 'Product on pure white background, professional studio lighting, e-commerce quality, sharp focus, clean shadows',
                    ),
                    array(
                        'title' => 'Lifestyle Product Shot',
                        'prompt' => 'Product in lifestyle setting, natural environment, contextual usage, professional photography, authentic feel',
                    ),
                    array(
                        'title' => 'Product Detail Close-up',
                        'prompt' => 'Close-up product detail shot, macro photography, high resolution, professional lighting, texture emphasis',
                    ),
                    array(
                        'title' => 'Floating Product',
                        'prompt' => 'Product floating in mid-air, creative composition, professional lighting, modern e-commerce style, clean background',
                    ),
                    array(
                        'title' => 'Flat Lay Product',
                        'prompt' => 'Flat lay product photography, overhead view, styled composition, complementary props, Instagram-worthy aesthetic',
                    ),
                ),
            ),
            'website-design' => array(
                'name' => 'AI-Imagen: Website Design',
                'description' => 'Hero images, illustrations',
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
                        'title' => 'Landing Page Visual',
                        'prompt' => 'Engaging landing page hero visual, conversion-focused, clear value proposition space, professional and trustworthy',
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
                'name' => 'AI-Imagen: Publishing',
                'description' => 'Article headers, covers',
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
                        'title' => 'Book Cover Art',
                        'prompt' => 'Book cover artwork, professional publishing quality, genre-appropriate aesthetic, print-ready design',
                    ),
                    array(
                        'title' => 'eBook Cover',
                        'prompt' => 'Digital eBook cover design, thumbnail-optimised, clear title visibility, professional self-publishing aesthetic',
                    ),
                    array(
                        'title' => 'Newsletter Header',
                        'prompt' => 'Engaging newsletter header design, brand-consistent, suitable for email publishing, professional and welcoming',
                    ),
                ),
            ),
            'presentations' => array(
                'name' => 'AI-Imagen: Presentations',
                'description' => 'Slide backgrounds, diagrams',
                'prompts' => array(
                    array(
                        'title' => 'Slide Background',
                        'prompt' => 'Professional presentation slide background, corporate aesthetic, subtle design, space for content, non-distracting',
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
                    array(
                        'title' => 'Infographic Slide',
                        'prompt' => 'Infographic presentation slide, data-driven design, clear visual communication, professional business aesthetic',
                    ),
                ),
            ),
            'game-development' => array(
                'name' => 'AI-Imagen: Game Development',
                'description' => 'Concept art, sprites',
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
                        'title' => 'Asset Sprite',
                        'prompt' => 'Game asset sprite, clean design, professional game art style, suitable for 2D games, pixel-perfect',
                    ),
                    array(
                        'title' => 'UI Element',
                        'prompt' => 'Game UI element design, modern interface style, professional game design, clear and functional',
                    ),
                    array(
                        'title' => 'Item Icon',
                        'prompt' => 'Game item icon, detailed illustration, recognisable design, suitable for inventory systems, professional quality',
                    ),
                ),
            ),
            'education' => array(
                'name' => 'AI-Imagen: Education',
                'description' => 'Diagrams, flashcards',
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
                    array(
                        'title' => 'Worksheet Header',
                        'prompt' => 'Friendly educational worksheet header, student-appropriate, encouraging learning environment, suitable for classroom',
                    ),
                ),
            ),
            'print-on-demand' => array(
                'name' => 'AI-Imagen: Print-on-Demand',
                'description' => 'T-shirts, stickers, posters',
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
                    array(
                        'title' => 'Tote Bag Graphic',
                        'prompt' => 'Tote bag graphic design, simple and stylish, print-ready quality, suitable for canvas printing, eco-friendly aesthetic',
                    ),
                ),
            ),

            // ROLES (8 categories)
            'marketing-manager' => array(
                'name' => 'AI-Imagen: Marketing Manager',
                'description' => 'Fast, on-brand campaigns',
                'prompts' => array(
                    array(
                        'title' => 'Campaign Visual',
                        'prompt' => 'Professional marketing campaign visual, on-brand aesthetic, high-impact design, suitable for multi-channel campaigns',
                    ),
                    array(
                        'title' => 'Brand Asset',
                        'prompt' => 'Consistent brand asset, corporate identity aligned, professional quality, suitable for marketing materials',
                    ),
                    array(
                        'title' => 'Email Header',
                        'prompt' => 'Email marketing header image, attention-grabbing, brand-consistent, optimised for email clients',
                    ),
                    array(
                        'title' => 'Landing Page Hero',
                        'prompt' => 'Landing page hero image, conversion-focused, professional design, clear value proposition space',
                    ),
                    array(
                        'title' => 'Ad Creative',
                        'prompt' => 'Digital ad creative, high-impact visual, brand-aligned, optimised for paid advertising campaigns',
                    ),
                ),
            ),
            'social-media-manager' => array(
                'name' => 'AI-Imagen: Social Media Manager',
                'description' => 'Trending visuals instantly',
                'prompts' => array(
                    array(
                        'title' => 'Trending Post',
                        'prompt' => 'Trending social media post visual, current aesthetic, highly shareable, optimised for engagement',
                    ),
                    array(
                        'title' => 'Story Content',
                        'prompt' => 'Engaging story content, vertical format, bold design, suitable for Instagram and Facebook stories',
                    ),
                    array(
                        'title' => 'Viral Visual',
                        'prompt' => 'Viral-worthy social visual, attention-grabbing, shareable design, optimised for social algorithms',
                    ),
                    array(
                        'title' => 'Community Post',
                        'prompt' => 'Community engagement post visual, authentic feel, relatable design, suitable for building connections',
                    ),
                    array(
                        'title' => 'Announcement Graphic',
                        'prompt' => 'Social media announcement graphic, clear messaging space, professional design, suitable for important updates',
                    ),
                ),
            ),
            'small-business-owner' => array(
                'name' => 'AI-Imagen: Small Business Owner',
                'description' => 'DIY product photography',
                'prompts' => array(
                    array(
                        'title' => 'DIY Product Shot',
                        'prompt' => 'Simple DIY product photography, clean background, natural lighting, professional-looking, easy to achieve',
                    ),
                    array(
                        'title' => 'Business Card Design',
                        'prompt' => 'Professional business card design, modern aesthetic, clear branding, suitable for small business',
                    ),
                    array(
                        'title' => 'Storefront Sign',
                        'prompt' => 'Attractive storefront sign design, welcoming aesthetic, clear branding, suitable for local business',
                    ),
                    array(
                        'title' => 'Promotional Flyer',
                        'prompt' => 'Eye-catching promotional flyer, small business focused, clear call-to-action, suitable for local marketing',
                    ),
                    array(
                        'title' => 'Menu Design',
                        'prompt' => 'Attractive menu design, appetising aesthetic, clear typography, professional food service presentation',
                    ),
                ),
            ),
            'graphic-designer' => array(
                'name' => 'AI-Imagen: Graphic Designer',
                'description' => 'Rapid ideation tools',
                'prompts' => array(
                    array(
                        'title' => 'Design Concept',
                        'prompt' => 'Creative design concept, modern aesthetic, professional quality, suitable for client presentations',
                    ),
                    array(
                        'title' => 'Mood Board Element',
                        'prompt' => 'Mood board visual element, inspirational aesthetic, professional design, suitable for creative direction',
                    ),
                    array(
                        'title' => 'Logo Concept',
                        'prompt' => 'Modern logo design concept, minimalist and memorable, scalable vector style, professional brand identity',
                    ),
                    array(
                        'title' => 'Pattern Design',
                        'prompt' => 'Seamless pattern design, repeatable tile, modern aesthetic, suitable for backgrounds or textile',
                    ),
                    array(
                        'title' => 'Typography Poster',
                        'prompt' => 'Bold typography poster design, impactful message, modern font pairing, suitable for promotional use',
                    ),
                ),
            ),
            'content-publisher' => array(
                'name' => 'AI-Imagen: Content Publisher',
                'description' => 'Editorial art, covers',
                'prompts' => array(
                    array(
                        'title' => 'Editorial Image',
                        'prompt' => 'Professional editorial image, journalism quality, relevant visual metaphor, publication-ready',
                    ),
                    array(
                        'title' => 'Book Cover',
                        'prompt' => 'Professional book cover design, genre-appropriate aesthetic, eye-catching composition, marketable',
                    ),
                    array(
                        'title' => 'Magazine Layout',
                        'prompt' => 'Modern magazine layout design, editorial aesthetic, clean typography, professional publishing standard',
                    ),
                    array(
                        'title' => 'Article Featured Image',
                        'prompt' => 'Compelling article featured image, blog-appropriate, SEO-friendly, professional publishing aesthetic',
                    ),
                    array(
                        'title' => 'Newsletter Visual',
                        'prompt' => 'Engaging newsletter visual, brand-consistent, suitable for email publishing, professional and welcoming',
                    ),
                ),
            ),
            'developer' => array(
                'name' => 'AI-Imagen: Developer',
                'description' => 'Auto-generated assets',
                'prompts' => array(
                    array(
                        'title' => 'App Icon',
                        'prompt' => 'Modern mobile app icon design, minimalist and recognisable, suitable for iOS and Android, scalable',
                    ),
                    array(
                        'title' => 'Dashboard UI',
                        'prompt' => 'Clean dashboard UI background, modern SaaS aesthetic, data-focused, professional tech interface',
                    ),
                    array(
                        'title' => 'Error Page Illustration',
                        'prompt' => 'Friendly 404 error page illustration, user-friendly, not intimidating, modern tech aesthetic',
                    ),
                    array(
                        'title' => 'Loading Screen',
                        'prompt' => 'Engaging loading screen design, modern tech aesthetic, brand-appropriate, reduces perceived wait time',
                    ),
                    array(
                        'title' => 'API Documentation Header',
                        'prompt' => 'Professional API documentation header, developer-focused, clean and technical, suitable for technical docs',
                    ),
                ),
            ),
            'educator' => array(
                'name' => 'AI-Imagen: Educator',
                'description' => 'Custom diagrams',
                'prompts' => array(
                    array(
                        'title' => 'Custom Diagram',
                        'prompt' => 'Custom educational diagram, clear and informative, professional teaching material, easy to understand',
                    ),
                    array(
                        'title' => 'Course Thumbnail',
                        'prompt' => 'Engaging online course thumbnail, professional educational aesthetic, clear subject indication',
                    ),
                    array(
                        'title' => 'Presentation Slide',
                        'prompt' => 'Professional presentation slide background, clean and minimal, suitable for academic presentation',
                    ),
                    array(
                        'title' => 'Certificate Background',
                        'prompt' => 'Formal certificate background, professional achievement aesthetic, elegant borders, suitable for awards',
                    ),
                    array(
                        'title' => 'Learning Material',
                        'prompt' => 'Educational learning material visual, student-appropriate, encouraging learning environment, classroom-ready',
                    ),
                ),
            ),
            'event-planner' => array(
                'name' => 'AI-Imagen: Event Planner',
                'description' => 'Posters and invites',
                'prompts' => array(
                    array(
                        'title' => 'Event Poster',
                        'prompt' => 'Professional event poster design, eye-catching visual, clear information hierarchy, suitable for promotion',
                    ),
                    array(
                        'title' => 'Elegant Invitation',
                        'prompt' => 'Elegant event invitation design, sophisticated aesthetic, professional quality, suitable for formal events',
                    ),
                    array(
                        'title' => 'Festival Promotional',
                        'prompt' => 'Vibrant festival promotional visual, energetic and dynamic, bold typography, youth-oriented design',
                    ),
                    array(
                        'title' => 'Corporate Event Visual',
                        'prompt' => 'Professional corporate event visual, modern business aesthetic, clear branding, suitable for conferences',
                    ),
                    array(
                        'title' => 'Save the Date Card',
                        'prompt' => 'Beautiful save the date card design, romantic or professional aesthetic, memorable design, event-appropriate',
                    ),
                ),
            ),

            // STYLES (9 categories)
            'photorealistic' => array(
                'name' => 'AI-Imagen: Photorealistic',
                'description' => 'DSLR, cinematic quality',
                'prompts' => array(
                    array(
                        'title' => 'DSLR Photography Style',
                        'prompt' => 'Photorealistic DSLR photography style, professional camera quality, shallow depth of field, natural lighting, high resolution, cinematic composition',
                    ),
                    array(
                        'title' => 'Cinematic Shot',
                        'prompt' => 'Cinematic quality photograph, film-like aesthetic, dramatic lighting, professional colour grading, movie production quality',
                    ),
                    array(
                        'title' => 'Studio Photography',
                        'prompt' => 'Professional studio photography, controlled lighting, photorealistic detail, commercial photography quality, sharp focus',
                    ),
                    array(
                        'title' => 'Natural Light Portrait',
                        'prompt' => 'Photorealistic natural light portrait, authentic feel, professional photography, DSLR quality, beautiful bokeh',
                    ),
                    array(
                        'title' => 'Architectural Photography',
                        'prompt' => 'Photorealistic architectural photography, professional real estate style, HDR quality, sharp details, perfect perspective',
                    ),
                ),
            ),
            'flat-minimalist' => array(
                'name' => 'AI-Imagen: Flat & Minimalist',
                'description' => 'Clean, simple designs',
                'prompts' => array(
                    array(
                        'title' => 'Flat Design Illustration',
                        'prompt' => 'Clean flat design illustration, minimalist aesthetic, simple shapes, limited colour palette, modern and professional',
                    ),
                    array(
                        'title' => 'Minimalist Composition',
                        'prompt' => 'Minimalist composition, negative space emphasis, simple and elegant, clean lines, sophisticated simplicity',
                    ),
                    array(
                        'title' => 'Geometric Flat Design',
                        'prompt' => 'Geometric flat design, simple shapes and patterns, modern minimalist aesthetic, clean and professional',
                    ),
                    array(
                        'title' => 'Simple Icon Style',
                        'prompt' => 'Simple flat icon style, minimalist design, clean and recognisable, suitable for UI/UX, scalable',
                    ),
                    array(
                        'title' => 'Clean Vector Art',
                        'prompt' => 'Clean vector art, flat design aesthetic, minimalist approach, professional and modern, simple colour scheme',
                    ),
                ),
            ),
            'cartoon-anime' => array(
                'name' => 'AI-Imagen: Cartoon & Anime',
                'description' => 'Illustrated characters',
                'prompts' => array(
                    array(
                        'title' => 'Cartoon Character',
                        'prompt' => 'Cute cartoon character illustration, friendly and approachable, vibrant colours, suitable for children and family content',
                    ),
                    array(
                        'title' => 'Anime Style Art',
                        'prompt' => 'Anime style illustration, Japanese animation aesthetic, expressive characters, detailed shading, manga-inspired',
                    ),
                    array(
                        'title' => 'Comic Book Style',
                        'prompt' => 'Comic book style illustration, bold outlines, dynamic composition, action-packed, vibrant colours',
                    ),
                    array(
                        'title' => 'Chibi Character',
                        'prompt' => 'Adorable chibi character design, super-deformed style, cute and playful, suitable for stickers and merchandise',
                    ),
                    array(
                        'title' => 'Animated Series Style',
                        'prompt' => 'Animated TV series style, consistent character design, suitable for animation production, professional cartoon aesthetic',
                    ),
                ),
            ),
            'digital-painting' => array(
                'name' => 'AI-Imagen: Digital Painting',
                'description' => 'Fantasy, sci-fi art',
                'prompts' => array(
                    array(
                        'title' => 'Fantasy Digital Painting',
                        'prompt' => 'Epic fantasy digital painting, detailed brushwork, magical atmosphere, concept art quality, suitable for book covers',
                    ),
                    array(
                        'title' => 'Sci-Fi Concept Art',
                        'prompt' => 'Futuristic sci-fi concept art, detailed digital painting, advanced technology aesthetic, cinematic quality',
                    ),
                    array(
                        'title' => 'Character Portrait',
                        'prompt' => 'Detailed character portrait, digital painting style, fantasy or sci-fi aesthetic, professional concept art quality',
                    ),
                    array(
                        'title' => 'Environment Matte Painting',
                        'prompt' => 'Epic environment matte painting, detailed landscape, fantasy or sci-fi setting, cinematic composition',
                    ),
                    array(
                        'title' => 'Creature Design',
                        'prompt' => 'Detailed creature design, digital painting style, fantasy or sci-fi aesthetic, concept art quality',
                    ),
                ),
            ),
            'retro-vintage' => array(
                'name' => 'AI-Imagen: Retro & Vintage',
                'description' => 'Nostalgic aesthetics',
                'prompts' => array(
                    array(
                        'title' => '80s Retro Style',
                        'prompt' => '1980s retro aesthetic, neon colours, synthwave style, nostalgic vibe, vintage technology feel',
                    ),
                    array(
                        'title' => 'Vintage Poster',
                        'prompt' => 'Vintage poster design, aged paper texture, retro typography, nostalgic colour palette, classic advertising style',
                    ),
                    array(
                        'title' => '50s Americana',
                        'prompt' => '1950s Americana style, mid-century modern aesthetic, vintage advertising, nostalgic and cheerful',
                    ),
                    array(
                        'title' => 'Retro Futurism',
                        'prompt' => 'Retro futurism aesthetic, 1960s vision of the future, vintage sci-fi style, nostalgic technology',
                    ),
                    array(
                        'title' => 'Vintage Photography',
                        'prompt' => 'Vintage photography style, aged photo aesthetic, nostalgic colour grading, classic film camera look',
                    ),
                ),
            ),
            '3d-cgi' => array(
                'name' => 'AI-Imagen: 3D & CGI',
                'description' => 'Rendered, isometric',
                'prompts' => array(
                    array(
                        'title' => '3D Rendered Scene',
                        'prompt' => 'Professional 3D rendered scene, photorealistic CGI, detailed textures, professional lighting, cinema 4D quality',
                    ),
                    array(
                        'title' => 'Isometric Illustration',
                        'prompt' => 'Isometric 3D illustration, clean and modern, suitable for infographics and tech visualisations, professional render',
                    ),
                    array(
                        'title' => 'Product Render',
                        'prompt' => 'Photorealistic 3D product render, studio lighting, perfect reflections, commercial quality CGI',
                    ),
                    array(
                        'title' => 'Low Poly 3D',
                        'prompt' => 'Low poly 3D art style, geometric shapes, modern aesthetic, suitable for games and illustrations',
                    ),
                    array(
                        'title' => 'Architectural Render',
                        'prompt' => 'Professional architectural 3D render, photorealistic quality, detailed materials, perfect lighting',
                    ),
                ),
            ),
            'hand-drawn' => array(
                'name' => 'AI-Imagen: Hand-drawn',
                'description' => 'Watercolour, sketch',
                'prompts' => array(
                    array(
                        'title' => 'Watercolour Painting',
                        'prompt' => 'Beautiful watercolour painting style, soft colours, artistic brushstrokes, traditional art aesthetic, elegant and delicate',
                    ),
                    array(
                        'title' => 'Pencil Sketch',
                        'prompt' => 'Hand-drawn pencil sketch, artistic line work, traditional drawing style, authentic sketch aesthetic',
                    ),
                    array(
                        'title' => 'Ink Drawing',
                        'prompt' => 'Hand-drawn ink illustration, bold line work, traditional pen and ink style, artistic and expressive',
                    ),
                    array(
                        'title' => 'Charcoal Art',
                        'prompt' => 'Charcoal drawing style, dramatic shading, traditional art medium, expressive and moody',
                    ),
                    array(
                        'title' => 'Pastel Illustration',
                        'prompt' => 'Soft pastel illustration, gentle colours, traditional art style, dreamy and artistic aesthetic',
                    ),
                ),
            ),
            'brand-layouts' => array(
                'name' => 'AI-Imagen: Brand Layouts',
                'description' => 'Magazine, social banners',
                'prompts' => array(
                    array(
                        'title' => 'Magazine Layout',
                        'prompt' => 'Professional magazine layout design, editorial aesthetic, clean typography, grid-based composition, publication-ready',
                    ),
                    array(
                        'title' => 'Social Media Banner',
                        'prompt' => 'Brand-consistent social media banner, professional design, optimised dimensions, suitable for multiple platforms',
                    ),
                    array(
                        'title' => 'Brand Template',
                        'prompt' => 'Professional brand template design, consistent visual identity, suitable for marketing materials, on-brand aesthetic',
                    ),
                    array(
                        'title' => 'Editorial Spread',
                        'prompt' => 'Magazine editorial spread design, professional layout, balanced composition, suitable for print and digital',
                    ),
                    array(
                        'title' => 'Marketing Collateral',
                        'prompt' => 'Brand-aligned marketing collateral, professional design system, consistent visual language, multi-purpose template',
                    ),
                ),
            ),
            'transparent-assets' => array(
                'name' => 'AI-Imagen: Transparent Assets',
                'description' => 'Stickers, cut-outs',
                'prompts' => array(
                    array(
                        'title' => 'Transparent Sticker',
                        'prompt' => 'Transparent background sticker design, clean cut-out, suitable for print-on-demand, vibrant colours, clear edges',
                    ),
                    array(
                        'title' => 'PNG Cut-out',
                        'prompt' => 'Clean PNG cut-out with transparent background, perfect edges, suitable for graphic design, no background artifacts',
                    ),
                    array(
                        'title' => 'Die-Cut Design',
                        'prompt' => 'Die-cut ready design, transparent background, clean outline, suitable for stickers and decals, print-ready',
                    ),
                    array(
                        'title' => 'Isolated Object',
                        'prompt' => 'Isolated object on transparent background, clean extraction, suitable for compositing, professional cut-out',
                    ),
                    array(
                        'title' => 'Transparent Icon',
                        'prompt' => 'Transparent background icon, clean edges, suitable for overlays and graphic design, scalable PNG',
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

