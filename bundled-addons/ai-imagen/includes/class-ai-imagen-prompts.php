<?php
/**
 * AI-Imagen Prompts Class
 * 
 * Manages prompt templates and library integration
 * 
 * @package AI_Imagen
 * @version 0.6.2
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
                        'prompt' => 'Create an image for a professional marketing campaign banner with a bold call-to-action, clear focal point, high-contrast composition, safe margins for copy, and export-ready web quality.',
                    ),
                    array(
                        'title' => 'Product Shot Ad',
                        'prompt' => 'Create an image of a commercial product shot for advertising with studio lighting on pure white (#FFFFFF), e-commerce grade, tack-sharp detail, soft realistic shadow, controlled reflections, and 4K+ export-ready clarity.',
                    ),
                    array(
                        'title' => 'Brand Campaign Visual',
                        'prompt' => 'Create an image for a modern brand campaign visual that supports bold typography, uses vibrant but brand-safe colours, follows a clean grid, and leaves generous negative space for headlines.',
                    ),
                    array(
                        'title' => 'Promotional Banner',
                        'prompt' => 'Create an image for a promotional banner focused on a sale; attention-grabbing focal point, clear value proposition space, strong visual hierarchy, web-safe colours, and high legibility at small sizes.',
                    ),
                    array(
                        'title' => 'Display Ad Creative',
                        'prompt' => 'Create an image for a high-impact display ad creative optimised for digital platforms; distinct subject, uncluttered background, strong contrast, crisp edges, and safe margins for overlaid text.',
                    ),
                ),
            ),
            'social-media' => array(
                'name' => 'AI-Imagen: Social Media',
                'description' => 'Posts, stories, thumbnails',
                'prompts' => array(
                    array(
                        'title' => 'Instagram Post',
                        'prompt' => 'Create an image for a square Instagram post with vibrant colours, a clear focal subject, modern composition, and mobile-first legibility with safe margins for captions.',
                    ),
                    array(
                        'title' => 'Story Background',
                        'prompt' => 'Create an image for a vertical 9:16 story background with a bold but uncluttered design, generous safe areas at the top and bottom for text and UI, optimised for mobile.',
                    ),
                    array(
                        'title' => 'Social Thumbnail',
                        'prompt' => 'Create an image for a social video thumbnail with high contrast, a strong silhouette, minimal background clutter, and readability at very small sizes.',
                    ),
                    array(
                        'title' => 'Quote Card',
                        'prompt' => 'Create an image for an inspirational quote card background with an elegant, soft colour palette, subtle texture, and ample clean space for typography.',
                    ),
                    array(
                        'title' => 'Carousel Slide',
                        'prompt' => 'Create an image for a social carousel slide with consistent branding, clear visual hierarchy, sequence-friendly edges, and reserved space for captions.',
                    ),
                ),
            ),
            'product-photography' => array(
                'name' => 'AI-Imagen: Product Photography',
                'description' => 'Professional product images',
                'prompts' => array(
                    array(
                        'title' => 'White Background Product',
                        'prompt' => 'Create an image of a product on a pure white background with professional studio lighting; e-commerce ready, true white (#FFFFFF) with soft natural shadow and crisp, sharp details.',
                    ),
                    array(
                        'title' => 'Lifestyle Product Shot',
                        'prompt' => 'Create an image of a product in a lifestyle setting that shows context of use; natural-light look, authentic styling, balanced composition, and commercial-quality polish.',
                    ),
                    array(
                        'title' => 'Product Detail Close-up',
                        'prompt' => 'Create an image of a close-up product detail (macro) highlighting texture and materials with high resolution, controlled lighting, and pinpoint focus on the key area.',
                    ),
                    array(
                        'title' => 'Floating Product',
                        'prompt' => 'Create an image of a product floating in mid-air with a subtle realistic shadow, modern e-commerce aesthetic, clean background, and balanced negative space.',
                    ),
                    array(
                        'title' => 'Flat Lay Product',
                        'prompt' => 'Create an image of a flat lay product scene from overhead with neatly styled props, even lighting, colour-coordinated set, and an Instagram-friendly look.',
                    ),
                ),
            ),
            'website-design' => array(
                'name' => 'AI-Imagen: Website Design',
                'description' => 'Hero images, illustrations',
                'prompts' => array(
                    array(
                        'title' => 'Hero Section Background',
                        'prompt' => 'Create an image for a modern website hero background using abstract geometric shapes, subtle gradients, web-optimised colours, and spacious safe area for a headline.',
                    ),
                    array(
                        'title' => 'Feature Illustration',
                        'prompt' => 'Create an image of a clean vector-style illustration for a website feature section with flat design, consistent stroke weights, and a limited brand-aligned palette.',
                    ),
                    array(
                        'title' => 'Landing Page Visual',
                        'prompt' => 'Create an image for a landing page hero visual that is conversion-oriented with a clear focal subject, generous negative space for copy, and a trustworthy, modern aesthetic.',
                    ),
                    array(
                        'title' => 'Icon Set Element',
                        'prompt' => 'Create an image of a minimalist icon element in flat style with consistent line weight, pixel-snapped edges, and a scalable appearance.',
                    ),
                    array(
                        'title' => 'Background Pattern',
                        'prompt' => 'Create an image of a subtle website background pattern that is geometric, low contrast, repeat-friendly, and non-distracting behind text.',
                    ),
                ),
            ),
            'publishing' => array(
                'name' => 'AI-Imagen: Publishing',
                'description' => 'Article headers, covers',
                'prompts' => array(
                    array(
                        'title' => 'Article Header',
                        'prompt' => 'Create an image for an editorial article header using a tasteful visual metaphor, newsroom quality, a clear subject, and a safe area for headline overlay.',
                    ),
                    array(
                        'title' => 'Magazine Cover',
                        'prompt' => 'Create an image for base artwork for a magazine cover with bold impact, clean background, and space for masthead and cover lines; high-resolution print-ready feel.',
                    ),
                    array(
                        'title' => 'Book Cover Art',
                        'prompt' => 'Create an image for book cover artwork with a genre-appropriate mood, strong central concept, print-ready clarity, and ample trim/safe areas.',
                    ),
                    array(
                        'title' => 'eBook Cover',
                        'prompt' => 'Create an image for a digital eBook cover prioritising thumbnail readability with high contrast title area, simple background, and razor-sharp export.',
                    ),
                    array(
                        'title' => 'Newsletter Header',
                        'prompt' => 'Create an image for a newsletter header that is brand-consistent, welcoming, lightweight, and optimised for fast loading across email clients.',
                    ),
                ),
            ),
            'presentations' => array(
                'name' => 'AI-Imagen: Presentations',
                'description' => 'Slide backgrounds, diagrams',
                'prompts' => array(
                    array(
                        'title' => 'Slide Background',
                        'prompt' => 'Create an image for a presentation slide background with a corporate-friendly, subtle texture or gradient that remains non-distracting with generous margins.',
                    ),
                    array(
                        'title' => 'Data Visualisation',
                        'prompt' => 'Create an image of a business data visualisation graphic with clean chart styling, legible labels, presentation-ready spacing, and a neutral accessible palette.',
                    ),
                    array(
                        'title' => 'Concept Diagram',
                        'prompt' => 'Create an image of a business concept diagram with clear hierarchy, labelled nodes and flows, tidy spacing, and slide-friendly formatting.',
                    ),
                    array(
                        'title' => 'Title Slide',
                        'prompt' => 'Create an image for a presentation title slide background that is bold yet professional with a strong focal motif and ample space for title and subtitle.',
                    ),
                    array(
                        'title' => 'Infographic Slide',
                        'prompt' => 'Create an image for an infographic slide with a data-first layout, tidy icons, clearly separated sections, and accessible contrast.',
                    ),
                ),
            ),
            'game-development' => array(
                'name' => 'AI-Imagen: Game Development',
                'description' => 'Concept art, sprites',
                'prompts' => array(
                    array(
                        'title' => 'Character Concept',
                        'prompt' => 'Create an image of game character concept art with a clear silhouette, front three-quarter pose, readable costume and materials, and concept sheet polish.',
                    ),
                    array(
                        'title' => 'Environment Art',
                        'prompt' => 'Create an image of game environment concept art with atmospheric depth, coherent lighting, a guided focal path, and rich but readable detail.',
                    ),
                    array(
                        'title' => 'Asset Sprite',
                        'prompt' => 'Create an image of a game asset sprite with clean outlines, consistent pixel scale, a transparent background, and readiness for 2D engines.',
                    ),
                    array(
                        'title' => 'UI Element',
                        'prompt' => 'Create an image of a game UI element with a modern interface look, high readability at multiple resolutions, and consistent padding and grids.',
                    ),
                    array(
                        'title' => 'Item Icon',
                        'prompt' => 'Create an image of a game item icon with distinct shape language, crisp edges, recognisability at 32–64 px, and a transparent background.',
                    ),
                ),
            ),
            'education' => array(
                'name' => 'AI-Imagen: Education',
                'description' => 'Diagrams, flashcards',
                'prompts' => array(
                    array(
                        'title' => 'Educational Diagram',
                        'prompt' => 'Create an image of a clear educational diagram using simple shapes, step-by-step logic, teacher-friendly labelling, and accessible colours.',
                    ),
                    array(
                        'title' => 'Flashcard Visual',
                        'prompt' => 'Create an image for an educational flashcard with a bold central illustration, minimal distractions, a child-friendly palette, and legible details.',
                    ),
                    array(
                        'title' => 'Classroom Poster',
                        'prompt' => 'Create an image for a classroom poster with an engaging hero visual, space for headings, and a high-resolution print-oriented feel.',
                    ),
                    array(
                        'title' => 'Study Guide Graphic',
                        'prompt' => 'Create an image for a study guide visual with an organised layout, numbered sections, consistent icons, and easy scanning.',
                    ),
                    array(
                        'title' => 'Worksheet Header',
                        'prompt' => 'Create an image for a worksheet header with a friendly motif, clean area for title/date/name, and a school-appropriate tone.',
                    ),
                ),
            ),
            'print-on-demand' => array(
                'name' => 'AI-Imagen: Print-on-Demand',
                'description' => 'T-shirts, stickers, posters',
                'prompts' => array(
                    array(
                        'title' => 'T-Shirt Graphic',
                        'prompt' => 'Create an image of a bold, eye-catching T-shirt graphic design that is print-ready, high resolution, vector-style, with a transparent background and centred, balanced composition.',
                    ),
                    array(
                        'title' => 'Sticker Design',
                        'prompt' => 'Create an image of a sticker design with a transparent background, bold colours, clean die-cut edges, print-ready resolution, and a simple readable silhouette.',
                    ),
                    array(
                        'title' => 'Poster Art',
                        'prompt' => 'Create an image of poster artwork with high resolution, strong visual impact, clean margins for typography, and a print-ready, professional finish.',
                    ),
                    array(
                        'title' => 'Mug Design',
                        'prompt' => 'Create an image of a mug wrap design suitable for 360-degree printing with bold colours, print-ready resolution, and clear separation from the background.',
                    ),
                    array(
                        'title' => 'Tote Bag Graphic',
                        'prompt' => 'Create an image of a tote bag graphic that is simple, stylish, print-ready, suitable for canvas printing, and uses a limited high-contrast palette.',
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
                        'prompt' => 'Create an image for a marketing campaign visual that is on-brand, high-impact, multi-channel safe, and leaves negative space for copy and CTAs.',
                    ),
                    array(
                        'title' => 'Brand Asset',
                        'prompt' => 'Create an image of a consistent brand asset aligned to corporate identity with professional finish and flexibility for marketing materials.',
                    ),
                    array(
                        'title' => 'Email Header',
                        'prompt' => 'Create an image for an email marketing header that is attention-grabbing, brand-consistent, lightweight, and optimised for common email clients.',
                    ),
                    array(
                        'title' => 'Landing Page Hero',
                        'prompt' => 'Create an image for a landing page hero that is conversion-focused with a clear value proposition area, trustworthy look, and fast-loading graphics.',
                    ),
                    array(
                        'title' => 'Ad Creative',
                        'prompt' => 'Create an image for digital ad creative with high-impact visuals, brand alignment, and optimisation for paid advertising placements.',
                    ),
                ),
            ),
            'social-media-manager' => array(
                'name' => 'AI-Imagen: Social Media Manager',
                'description' => 'Trending visuals instantly',
                'prompts' => array(
                    array(
                        'title' => 'Trending Post',
                        'prompt' => 'Create an image for a trending social media post with a current aesthetic, strong shareability, and high engagement potential.',
                    ),
                    array(
                        'title' => 'Story Content',
                        'prompt' => 'Create an image for vertical story content with bold design, large safe areas for text, and mobile-first clarity.',
                    ),
                    array(
                        'title' => 'Viral Visual',
                        'prompt' => 'Create an image designed to be highly shareable on social platforms with an attention-grabbing focal point and algorithm-friendly clarity.',
                    ),
                    array(
                        'title' => 'Community Post',
                        'prompt' => 'Create an image for a community engagement post with an authentic feel, relatable design, and warmth that encourages interaction.',
                    ),
                    array(
                        'title' => 'Announcement Graphic',
                        'prompt' => 'Create an image for a social announcement with clear messaging space, professional layout, and strong visibility in feeds.',
                    ),
                ),
            ),
            'small-business-owner' => array(
                'name' => 'AI-Imagen: Small Business Owner',
                'description' => 'DIY product photography',
                'prompts' => array(
                    array(
                        'title' => 'DIY Product Shot',
                        'prompt' => 'Create an image that looks like a simple DIY product photo with a clean background, natural-light look, and professional appearance achievable with minimal setup.',
                    ),
                    array(
                        'title' => 'Business Card Design',
                        'prompt' => 'Create an image of a professional business card base design with a modern aesthetic, clear branding, and print-friendly proportions.',
                    ),
                    array(
                        'title' => 'Storefront Sign',
                        'prompt' => 'Create an image of an attractive storefront sign concept with a welcoming tone, strong branding, and readable typography.',
                    ),
                    array(
                        'title' => 'Promotional Flyer',
                        'prompt' => 'Create an image for an eye-catching promotional flyer with a clear call-to-action and local marketing focus.',
                    ),
                    array(
                        'title' => 'Menu Design',
                        'prompt' => 'Create an image of an appealing menu layout with appetising cues, clear typography, and professional food-service presentation.',
                    ),
                ),
            ),
            'graphic-designer' => array(
                'name' => 'AI-Imagen: Graphic Designer',
                'description' => 'Rapid ideation tools',
                'prompts' => array(
                    array(
                        'title' => 'Design Concept',
                        'prompt' => 'Create an image of a creative design concept with a modern aesthetic, professional finish, and suitability for client presentations.',
                    ),
                    array(
                        'title' => 'Mood Board Element',
                        'prompt' => 'Create an image of a mood board element with an inspirational look, cohesive visual language, and clarity for creative direction.',
                    ),
                    array(
                        'title' => 'Logo Concept',
                        'prompt' => 'Create an image of a modern logo concept with minimalist, memorable form, vector-style crispness, and brand-identity utility.',
                    ),
                    array(
                        'title' => 'Pattern Design',
                        'prompt' => 'Create an image of a seamless, repeatable pattern with a modern aesthetic suitable for backgrounds or textiles.',
                    ),
                    array(
                        'title' => 'Typography Poster',
                        'prompt' => 'Create an image of a bold typography poster with impactful message area, strong font pairing, and promotional clarity.',
                    ),
                ),
            ),
            'content-publisher' => array(
                'name' => 'AI-Imagen: Content Publisher',
                'description' => 'Editorial art, covers',
                'prompts' => array(
                    array(
                        'title' => 'Editorial Image',
                        'prompt' => 'Create an image for editorial use with journalism-grade clarity, relevant metaphor, and publication-ready polish.',
                    ),
                    array(
                        'title' => 'Book Cover',
                        'prompt' => 'Create an image suitable for a professional book cover with genre-appropriate cues, eye-catching composition, and marketable clarity.',
                    ),
                    array(
                        'title' => 'Magazine Layout',
                        'prompt' => 'Create an image representing a modern magazine layout base with editorial styling, clean typography space, and professional hierarchy.',
                    ),
                    array(
                        'title' => 'Article Featured Image',
                        'prompt' => 'Create an image suitable for a blog featured image with a compelling subject, SEO-friendly clarity at thumbnail size, and clean background.',
                    ),
                    array(
                        'title' => 'Newsletter Visual',
                        'prompt' => 'Create an image for a newsletter visual that is brand-consistent, lightweight, and welcoming for email publishing.',
                    ),
                ),
            ),
            'developer' => array(
                'name' => 'AI-Imagen: Developer',
                'description' => 'Auto-generated assets',
                'prompts' => array(
                    array(
                        'title' => 'App Icon',
                        'prompt' => 'Create an image of a modern mobile app icon with minimalist, recognisable form, suitable for iOS and Android, and a crisp scalable look.',
                    ),
                    array(
                        'title' => 'Dashboard UI',
                        'prompt' => 'Create an image of a clean dashboard UI background with a modern SaaS aesthetic, data-focused layout hints, and a professional interface feel.',
                    ),
                    array(
                        'title' => 'Error Page Illustration',
                        'prompt' => 'Create an image for a friendly 404 error page illustration with a reassuring tone and modern tech aesthetic.',
                    ),
                    array(
                        'title' => 'Loading Screen',
                        'prompt' => 'Create an image for a loading screen with engaging but lightweight visuals that reduce perceived wait time and fit brand style.',
                    ),
                    array(
                        'title' => 'API Documentation Header',
                        'prompt' => 'Create an image for an API documentation header with a developer-focused, clean, technical look suitable for technical docs.',
                    ),
                ),
            ),
            'educator' => array(
                'name' => 'AI-Imagen: Educator',
                'description' => 'Custom diagrams',
                'prompts' => array(
                    array(
                        'title' => 'Custom Diagram',
                        'prompt' => 'Create an image of a custom educational diagram that is clear, informative, and easy to understand in a classroom setting.',
                    ),
                    array(
                        'title' => 'Course Thumbnail',
                        'prompt' => 'Create an image for an online course thumbnail with a professional educational look and clear subject indication at small sizes.',
                    ),
                    array(
                        'title' => 'Presentation Slide',
                        'prompt' => 'Create an image for a presentation slide background that is clean and minimal, suitable for academic presentations without distraction.',
                    ),
                    array(
                        'title' => 'Certificate Background',
                        'prompt' => 'Create an image of a formal certificate background with elegant borders and a professional achievement tone.',
                    ),
                    array(
                        'title' => 'Learning Material',
                        'prompt' => 'Create an image for educational learning material with a student-appropriate style and a supportive classroom feel.',
                    ),
                ),
            ),
            'event-planner' => array(
                'name' => 'AI-Imagen: Event Planner',
                'description' => 'Posters and invites',
                'prompts' => array(
                    array(
                        'title' => 'Event Poster',
                        'prompt' => 'Create an image for a professional event poster with eye-catching visuals, a clear information hierarchy, and promotional clarity.',
                    ),
                    array(
                        'title' => 'Elegant Invitation',
                        'prompt' => 'Create an image for an elegant event invitation with a sophisticated tone, professional finish, and print-friendly layout.',
                    ),
                    array(
                        'title' => 'Festival Promotional',
                        'prompt' => 'Create an image for a vibrant festival promotional visual with energetic composition, bold typography support, and youth-oriented appeal.',
                    ),
                    array(
                        'title' => 'Corporate Event Visual',
                        'prompt' => 'Create an image for a corporate event visual with a modern business aesthetic, clear branding space, and conference-ready polish.',
                    ),
                    array(
                        'title' => 'Save the Date Card',
                        'prompt' => 'Create an image for a save-the-date card with a romantic or professional tone and a memorable, event-appropriate design.',
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
                        'prompt' => 'Create an image in a photorealistic DSLR photography style with professional camera quality, shallow depth of field, natural lighting, high resolution, and cinematic composition.',
                    ),
                    array(
                        'title' => 'Cinematic Shot',
                        'prompt' => 'Create an image with cinematic photographic quality, film-like grading, dramatic lighting, and a production-level finish.',
                    ),
                    array(
                        'title' => 'Studio Photography',
                        'prompt' => 'Create an image with professional studio photography qualities: controlled lighting, photorealistic detail, commercial sharpness, and clean backgrounds.',
                    ),
                    array(
                        'title' => 'Natural Light Portrait',
                        'prompt' => 'Create an image of a natural light portrait with authentic tone, DSLR-level clarity, and beautiful background bokeh.',
                    ),
                    array(
                        'title' => 'Architectural Photography',
                        'prompt' => 'Create an image in a photorealistic architectural photography style with HDR-like clarity, sharp details, and accurate perspective.',
                    ),
                ),
            ),
            'flat-minimalist' => array(
                'name' => 'AI-Imagen: Flat & Minimalist',
                'description' => 'Clean, simple designs',
                'prompts' => array(
                    array(
                        'title' => 'Flat Design Illustration',
                        'prompt' => 'Create an image of a clean flat illustration with a minimalist aesthetic, simple shapes, limited colour palette, and a professional finish.',
                    ),
                    array(
                        'title' => 'Minimalist Composition',
                        'prompt' => 'Create an image of a minimalist composition with strong negative space, clean lines, and elegant simplicity.',
                    ),
                    array(
                        'title' => 'Geometric Flat Design',
                        'prompt' => 'Create an image with geometric flat design using simple shapes and patterns, a modern minimalist aesthetic, and clean execution.',
                    ),
                    array(
                        'title' => 'Simple Icon Style',
                        'prompt' => 'Create an image of a simple flat icon with minimalist design, clean recognisable form, and UI-friendly scalability.',
                    ),
                    array(
                        'title' => 'Clean Vector Art',
                        'prompt' => 'Create an image of clean vector-style artwork with a flat design feel, minimalist approach, and simple coordinated colour scheme.',
                    ),
                ),
            ),
            'cartoon-anime' => array(
                'name' => 'AI-Imagen: Cartoon & Anime',
                'description' => 'Illustrated characters',
                'prompts' => array(
                    array(
                        'title' => 'Cartoon Character',
                        'prompt' => 'Create an image of a cute cartoon character illustration with a friendly, approachable tone and vibrant colours suitable for family content.',
                    ),
                    array(
                        'title' => 'Anime Style Art',
                        'prompt' => 'Create an image in an anime-style illustration with expressive characters and detailed shading inspired by manga.',
                    ),
                    array(
                        'title' => 'Comic Book Style',
                        'prompt' => 'Create an image in a comic book illustration style with bold outlines, dynamic composition, and vivid colours.',
                    ),
                    array(
                        'title' => 'Chibi Character',
                        'prompt' => 'Create an image of an adorable chibi character with super-deformed proportions, cute expression, and sticker-friendly clarity.',
                    ),
                    array(
                        'title' => 'Animated Series Style',
                        'prompt' => 'Create an image in a TV animation style with consistent character design and a professional cartoon aesthetic.',
                    ),
                ),
            ),
            'digital-painting' => array(
                'name' => 'AI-Imagen: Digital Painting',
                'description' => 'Fantasy, sci-fi art',
                'prompts' => array(
                    array(
                        'title' => 'Fantasy Digital Painting',
                        'prompt' => 'Create an image of an epic fantasy digital painting with detailed brushwork, magical atmosphere, and book-cover-ready clarity.',
                    ),
                    array(
                        'title' => 'Sci-Fi Concept Art',
                        'prompt' => 'Create an image of futuristic sci-fi concept art with detailed digital painting, advanced tech cues, and cinematic mood.',
                    ),
                    array(
                        'title' => 'Character Portrait',
                        'prompt' => 'Create an image of a detailed character portrait in digital painting style with genre-appropriate mood and concept art polish.',
                    ),
                    array(
                        'title' => 'Environment Matte Painting',
                        'prompt' => 'Create an image of an epic environment matte painting with rich landscape detail, fantasy or sci-fi setting, and cinematic composition.',
                    ),
                    array(
                        'title' => 'Creature Design',
                        'prompt' => 'Create an image of a detailed creature design in digital painting style with readable anatomy and concept art quality.',
                    ),
                ),
            ),
            'retro-vintage' => array(
                'name' => 'AI-Imagen: Retro & Vintage',
                'description' => 'Nostalgic aesthetics',
                'prompts' => array(
                    array(
                        'title' => '80s Retro Style',
                        'prompt' => 'Create an image with a 1980s retro aesthetic using neon colours, synthwave cues, and nostalgic tech vibes.',
                    ),
                    array(
                        'title' => 'Vintage Poster',
                        'prompt' => 'Create an image of a vintage poster with aged paper texture, retro typography, nostalgic palette, and classic advertising style.',
                    ),
                    array(
                        'title' => '50s Americana',
                        'prompt' => 'Create an image in a 1950s Americana style with mid-century modern cues, cheerful tone, and vintage advertising feel.',
                    ),
                    array(
                        'title' => 'Retro Futurism',
                        'prompt' => 'Create an image with retro futurism aesthetics inspired by 1960s visions of the future and nostalgic technology.',
                    ),
                    array(
                        'title' => 'Vintage Photography',
                        'prompt' => 'Create an image in a vintage photography style with aged-film grading, nostalgic colour tones, and classic camera look.',
                    ),
                ),
            ),
            '3d-cgi' => array(
                'name' => 'AI-Imagen: 3D & CGI',
                'description' => 'Rendered, isometric',
                'prompts' => array(
                    array(
                        'title' => '3D Rendered Scene',
                        'prompt' => 'Create an image of a professional 3D rendered scene with photorealistic CGI, detailed textures, and well-controlled lighting.',
                    ),
                    array(
                        'title' => 'Isometric Illustration',
                        'prompt' => 'Create an image of an isometric 3D illustration with a clean modern look suitable for infographics and tech visuals.',
                    ),
                    array(
                        'title' => 'Product Render',
                        'prompt' => 'Create an image of a photorealistic 3D product render with studio lighting, accurate reflections, and commercial CGI quality.',
                    ),
                    array(
                        'title' => 'Low Poly 3D',
                        'prompt' => 'Create an image in a low poly 3D art style with geometric forms and a modern, game-friendly aesthetic.',
                    ),
                    array(
                        'title' => 'Architectural Render',
                        'prompt' => 'Create an image of a professional architectural 3D render with photorealistic materials and perfect lighting.',
                    ),
                ),
            ),
            'hand-drawn' => array(
                'name' => 'AI-Imagen: Hand-drawn',
                'description' => 'Watercolour, sketch',
                'prompts' => array(
                    array(
                        'title' => 'Watercolour Painting',
                        'prompt' => 'Create an image in a beautiful watercolour painting style with soft colours, visible brushstrokes, and an elegant traditional feel.',
                    ),
                    array(
                        'title' => 'Pencil Sketch',
                        'prompt' => 'Create an image of a hand-drawn pencil sketch with expressive line work and an authentic traditional drawing look.',
                    ),
                    array(
                        'title' => 'Ink Drawing',
                        'prompt' => 'Create an image of a hand-drawn ink illustration with bold line work and expressive, traditional pen-and-ink style.',
                    ),
                    array(
                        'title' => 'Charcoal Art',
                        'prompt' => 'Create an image in a charcoal drawing style with dramatic shading and an expressive, moody tone.',
                    ),
                    array(
                        'title' => 'Pastel Illustration',
                        'prompt' => 'Create an image in a soft pastel illustration style with gentle colours and a dreamy, artistic feel.',
                    ),
                ),
            ),
            'brand-layouts' => array(
                'name' => 'AI-Imagen: Brand Layouts',
                'description' => 'Magazine, social banners',
                'prompts' => array(
                    array(
                        'title' => 'Magazine Layout',
                        'prompt' => 'Create an image for a professional magazine layout base with editorial styling, clean typography support, grid-based composition, and publication-ready clarity.',
                    ),
                    array(
                        'title' => 'Social Media Banner',
                        'prompt' => 'Create an image for a brand-consistent social media banner with professional design, multi-platform suitability, adaptive safe margins, and clear space for headlines and CTAs.',
                    ),
                    array(
                        'title' => 'Brand Template',
                        'prompt' => 'Create an image for a professional brand template with a consistent visual identity, reusable components, and a clean, on-brand aesthetic.',
                    ),
                    array(
                        'title' => 'Editorial Spread',
                        'prompt' => 'Create an image for a magazine editorial spread base with balanced composition, clear column structure, and suitability for print and digital.',
                    ),
                    array(
                        'title' => 'Marketing Collateral',
                        'prompt' => 'Create an image for brand-aligned marketing collateral with a professional design system, consistent visual language, and multi-purpose template clarity.',
                    ),
                ),
            ),
            'transparent-assets' => array(
                'name' => 'AI-Imagen: Transparent Assets',
                'description' => 'Stickers, cut-outs',
                'prompts' => array(
                    array(
                        'title' => 'Transparent Sticker',
                        'prompt' => 'Create an image of a sticker design with a transparent background, clean cut-out, vivid colours, and print-on-demand readiness.',
                    ),
                    array(
                        'title' => 'PNG Cut-out',
                        'prompt' => 'Create an image of a clean PNG cut-out on a transparent background with perfect edges and no background artefacts.',
                    ),
                    array(
                        'title' => 'Die-Cut Design',
                        'prompt' => 'Create an image of a die-cut ready design with a transparent background, clean outline, and print-ready clarity for stickers and decals.',
                    ),
                    array(
                        'title' => 'Isolated Object',
                        'prompt' => 'Create an image of an isolated object on a transparent background with professional extraction quality suitable for compositing.',
                    ),
                    array(
                        'title' => 'Transparent Icon',
                        'prompt' => 'Create an image of a transparent-background icon with clean edges, overlay-friendly clarity, and a crisp, scalable appearance.',
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

