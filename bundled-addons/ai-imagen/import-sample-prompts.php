<?php
/**
 * Import Sample AI-Imagen Prompts
 * 
 * Run this file once to populate the AI-Core Prompt Library with sample prompts
 * for AI-Imagen workflows.
 * 
 * Usage: Navigate to this file in your browser while logged in as admin
 * URL: /wp-content/plugins/ai-core/bundled-addons/ai-imagen/import-sample-prompts.php
 * 
 * @package AI_Imagen
 * @version 0.5.0
 */

// Load WordPress
require_once('../../../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Check if AI-Core Prompt Library class exists
if (!class_exists('AI_Core_Prompt_Library')) {
    wp_die('AI-Core Prompt Library class not found. Please ensure AI-Core plugin is active.');
}

$prompt_library = AI_Core_Prompt_Library::get_instance();

// Define sample prompts organised by category
$sample_data = array(
    // USE CASES (9 categories)
    'AI-Imagen: Marketing & Ads' => array(
        'description' => 'Campaign banners, product shots',
        'prompts' => array(
            array('title' => 'Campaign Banner', 'content' => 'Professional marketing campaign banner, bold call-to-action, eye-catching design, suitable for digital advertising, high conversion focus'),
            array('title' => 'Product Shot Ad', 'content' => 'Commercial product shot for advertising, studio lighting, white background, professional e-commerce style, sharp focus, clean composition'),
            array('title' => 'Brand Campaign Visual', 'content' => 'Modern brand campaign visual with bold typography, vibrant colours, professional corporate style, suitable for multi-channel marketing'),
            array('title' => 'Promotional Banner', 'content' => 'Eye-catching promotional banner, sale-focused, bright and engaging, clear value proposition, suitable for online advertising'),
            array('title' => 'Display Ad Creative', 'content' => 'High-impact display ad creative, attention-grabbing, optimised for digital platforms, professional advertising aesthetic'),
        ),
    ),
    'AI-Imagen: Social Media' => array(
        'description' => 'Posts, stories, thumbnails',
        'prompts' => array(
            array('title' => 'Instagram Post', 'content' => 'Vibrant Instagram post graphic, trendy aesthetic, bright colours, engaging composition, optimised for mobile viewing, square format'),
            array('title' => 'Story Template', 'content' => 'Engaging social media story template, vertical format, bold text overlay space, attention-grabbing, mobile-optimised, swipe-up ready'),
            array('title' => 'YouTube Thumbnail', 'content' => 'Click-worthy YouTube thumbnail, bold text, expressive imagery, high contrast, optimised for small preview size, professional'),
            array('title' => 'Facebook Post Visual', 'content' => 'Shareable Facebook post visual, engaging and relatable, suitable for organic reach, professional yet approachable aesthetic'),
            array('title' => 'LinkedIn Post Image', 'content' => 'Professional LinkedIn post visual, corporate aesthetic, business-appropriate, clean and polished, suitable for B2B content'),
        ),
    ),
    'AI-Imagen: Product Photography' => array(
        'description' => 'Professional product images',
        'prompts' => array(
            array('title' => 'White Background Product', 'content' => 'Professional product photo on pure white background, studio lighting, e-commerce style, sharp focus, no shadows, clean and minimal'),
            array('title' => 'Lifestyle Product Shot', 'content' => 'Lifestyle product photography, natural setting, authentic feel, soft lighting, product in use, relatable context'),
            array('title' => 'Flat Lay Product', 'content' => 'Flat lay product photography, overhead view, styled composition, complementary props, Instagram-worthy aesthetic'),
            array('title' => 'Luxury Product Shot', 'content' => 'Luxury product photography, dramatic lighting, premium feel, elegant composition, high-end commercial style'),
            array('title' => 'Product Detail Close-up', 'content' => 'Macro product detail shot, extreme close-up, texture emphasis, professional lighting, showcasing craftsmanship and quality'),
        ),
    ),
    'AI-Imagen: Website Design' => array(
        'description' => 'Hero images, illustrations',
        'prompts' => array(
            array('title' => 'Website Hero Image', 'content' => 'Modern website hero section background, abstract geometric patterns, professional corporate colours, suitable for tech company'),
            array('title' => 'Hero Illustration', 'content' => 'Custom hero section illustration, modern flat design style, brand-appropriate colours, suitable for SaaS landing page'),
            array('title' => 'Landing Page Visual', 'content' => 'Engaging landing page hero visual, conversion-focused, clear value proposition space, professional and trustworthy aesthetic'),
            array('title' => 'About Page Background', 'content' => 'Warm and welcoming about page background, team-focused, professional yet approachable, suitable for company culture showcase'),
            array('title' => 'Feature Section Graphic', 'content' => 'Modern feature section graphic, illustrative style, explains product benefits visually, clean and professional'),
        ),
    ),
    'AI-Imagen: Publishing' => array(
        'description' => 'Article headers, covers',
        'prompts' => array(
            array('title' => 'Article Header Image', 'content' => 'Compelling article header image, blog-appropriate, SEO-friendly, professional publishing aesthetic, shareable on social media'),
            array('title' => 'Book Cover', 'content' => 'Professional book cover design, genre-appropriate aesthetic, eye-catching composition, suitable for self-publishing, marketable'),
            array('title' => 'Magazine Cover', 'content' => 'Modern magazine cover design, editorial aesthetic, bold typography, professional publishing standard, newsstand-ready'),
            array('title' => 'eBook Cover', 'content' => 'Digital eBook cover design, thumbnail-optimised, clear title visibility, professional self-publishing aesthetic'),
            array('title' => 'Newsletter Header', 'content' => 'Engaging newsletter header design, brand-consistent, suitable for email publishing, professional and welcoming'),
        ),
    ),
    'AI-Imagen: Presentations' => array(
        'description' => 'Slide backgrounds, diagrams',
        'prompts' => array(
            array('title' => 'Presentation Slide Background', 'content' => 'Professional presentation slide background, clean and minimal, suitable for business presentation, non-distracting, corporate aesthetic'),
            array('title' => 'Infographic Diagram', 'content' => 'Clear infographic diagram, data visualisation, professional design, suitable for business presentations, easy to understand'),
            array('title' => 'Title Slide Design', 'content' => 'Impactful title slide design, bold and professional, suitable for keynote or pitch deck, memorable first impression'),
            array('title' => 'Process Diagram', 'content' => 'Clean process flow diagram, step-by-step visualisation, professional business aesthetic, suitable for explaining workflows'),
            array('title' => 'Data Visualisation', 'content' => 'Professional data visualisation background, chart-ready, clean grid, suitable for presenting statistics and metrics'),
        ),
    ),
    'AI-Imagen: Game Development' => array(
        'description' => 'Concept art, sprites',
        'prompts' => array(
            array('title' => 'Game Concept Art', 'content' => 'Professional game concept art, detailed environment design, fantasy or sci-fi aesthetic, suitable for game development pipeline'),
            array('title' => 'Character Sprite', 'content' => 'Game character sprite design, pixel art or 2D style, multiple angles, suitable for side-scrolling or top-down games'),
            array('title' => 'Environment Asset', 'content' => 'Game environment asset, tileable texture, seamless pattern, suitable for level design, professional game art quality'),
            array('title' => 'UI Icon Set', 'content' => 'Game UI icon set, consistent style, clear and recognisable, suitable for inventory or menu systems, scalable'),
            array('title' => 'Boss Character Design', 'content' => 'Epic boss character design, detailed and imposing, suitable for action or RPG games, memorable visual impact'),
        ),
    ),
    'AI-Imagen: Education' => array(
        'description' => 'Diagrams, flashcards',
        'prompts' => array(
            array('title' => 'Educational Diagram', 'content' => 'Clear educational diagram, learning-focused, organised layout, suitable for teaching materials, professional academic style'),
            array('title' => 'Flashcard Design', 'content' => 'Engaging flashcard design, student-friendly, clear visual hierarchy, suitable for memorisation and study aids'),
            array('title' => 'Course Thumbnail', 'content' => 'Engaging online course thumbnail, professional educational aesthetic, clear subject indication, suitable for e-learning platform'),
            array('title' => 'Worksheet Header', 'content' => 'Friendly educational worksheet header, student-appropriate, encouraging learning environment, suitable for classroom materials'),
            array('title' => 'Certificate Background', 'content' => 'Formal certificate background, professional achievement aesthetic, elegant borders, suitable for awards and recognition'),
        ),
    ),
    'AI-Imagen: Print-on-Demand' => array(
        'description' => 'T-shirts, stickers, posters',
        'prompts' => array(
            array('title' => 'T-Shirt Design', 'content' => 'Bold t-shirt graphic design, print-ready, suitable for apparel, trendy aesthetic, works on various colours, scalable vector style'),
            array('title' => 'Sticker Design', 'content' => 'Fun sticker design, die-cut ready, vibrant colours, suitable for print-on-demand, appealing to wide audience, clear outline'),
            array('title' => 'Poster Art', 'content' => 'Eye-catching poster art, suitable for wall decoration, high-resolution, professional print quality, marketable design'),
            array('title' => 'Mug Wrap Design', 'content' => 'Seamless mug wrap design, 360-degree pattern, suitable for print-on-demand, vibrant and appealing, coffee-themed'),
            array('title' => 'Phone Case Design', 'content' => 'Trendy phone case design, suitable for various phone models, protective aesthetic, marketable to young audience'),
        ),
    ),

    // ROLES (8 categories)
    'AI-Imagen: Marketing Manager' => array(
        'description' => 'Fast, on-brand campaigns',
        'prompts' => array(
            array('title' => 'Campaign Launch Visual', 'content' => 'Professional campaign launch visual, on-brand colours and style, fast turnaround aesthetic, suitable for multi-channel marketing'),
            array('title' => 'Brand Consistent Ad', 'content' => 'Brand-consistent advertisement, follows brand guidelines, professional marketing aesthetic, suitable for quick campaign deployment'),
            array('title' => 'Marketing Collateral', 'content' => 'Professional marketing collateral design, brand-aligned, suitable for print and digital, fast production ready'),
            array('title' => 'Campaign Hero Image', 'content' => 'Impactful campaign hero image, brand-focused, suitable for landing pages and email headers, conversion-optimised'),
            array('title' => 'Promotional Asset', 'content' => 'Quick promotional asset, on-brand aesthetic, suitable for time-sensitive campaigns, professional marketing quality'),
        ),
    ),
    'AI-Imagen: Social Media Manager' => array(
        'description' => 'Trending visuals instantly',
        'prompts' => array(
            array('title' => 'Trending Post Graphic', 'content' => 'Trendy social media post graphic, current aesthetic, viral-worthy design, optimised for engagement and shares'),
            array('title' => 'Instant Story Template', 'content' => 'Quick social media story template, trending style, mobile-optimised, suitable for daily content creation'),
            array('title' => 'Viral-Ready Visual', 'content' => 'Viral-ready social visual, attention-grabbing, follows current trends, suitable for maximum engagement'),
            array('title' => 'Quick Carousel Post', 'content' => 'Fast carousel post design, swipeable format, trendy aesthetic, suitable for Instagram and LinkedIn'),
            array('title' => 'Engagement Booster', 'content' => 'High-engagement social graphic, trending colours and style, suitable for quick content turnaround'),
        ),
    ),
    'AI-Imagen: Small Business Owner' => array(
        'description' => 'DIY product photography',
        'prompts' => array(
            array('title' => 'DIY Product Photo', 'content' => 'Simple DIY product photo, clean white background, professional look without studio, suitable for small business e-commerce'),
            array('title' => 'Budget-Friendly Product Shot', 'content' => 'Budget-friendly product photography, professional appearance, suitable for online store, no expensive equipment needed'),
            array('title' => 'Home Studio Product', 'content' => 'Home studio product photo, natural lighting aesthetic, professional yet accessible, suitable for small business marketing'),
            array('title' => 'Quick Product Listing', 'content' => 'Quick product listing photo, e-commerce ready, clean and simple, suitable for marketplace platforms'),
            array('title' => 'Small Business Showcase', 'content' => 'Small business product showcase, authentic and relatable, professional DIY aesthetic, suitable for social media'),
        ),
    ),
    'AI-Imagen: Graphic Designer' => array(
        'description' => 'Rapid ideation tools',
        'prompts' => array(
            array('title' => 'Design Concept Mockup', 'content' => 'Quick design concept mockup, professional presentation, suitable for client pitches, rapid ideation aesthetic'),
            array('title' => 'Creative Exploration', 'content' => 'Creative design exploration, multiple style variations, suitable for brainstorming and concept development'),
            array('title' => 'Mood Board Element', 'content' => 'Mood board visual element, inspirational aesthetic, suitable for design direction and client presentations'),
            array('title' => 'Design Asset Base', 'content' => 'Base design asset for further refinement, professional starting point, suitable for Adobe Creative Suite workflow'),
            array('title' => 'Rapid Prototype Visual', 'content' => 'Rapid prototype visual, quick iteration ready, suitable for design sprints and fast-paced projects'),
        ),
    ),
    'AI-Imagen: Content Publisher' => array(
        'description' => 'Editorial art, covers',
        'prompts' => array(
            array('title' => 'Editorial Feature Image', 'content' => 'Professional editorial feature image, publication-quality, suitable for articles and blog posts, SEO-optimised'),
            array('title' => 'Magazine Cover Art', 'content' => 'Eye-catching magazine cover art, newsstand-ready, bold typography space, professional publishing aesthetic'),
            array('title' => 'Article Hero Visual', 'content' => 'Compelling article hero visual, editorial style, suitable for long-form content, professional journalism aesthetic'),
            array('title' => 'Content Series Graphic', 'content' => 'Consistent content series graphic, brand-aligned, suitable for recurring columns or series, recognisable style'),
            array('title' => 'Publication Header', 'content' => 'Professional publication header, editorial aesthetic, suitable for newsletters and digital magazines'),
        ),
    ),
    'AI-Imagen: Developer' => array(
        'description' => 'Auto-generated assets',
        'prompts' => array(
            array('title' => 'App Icon Asset', 'content' => 'Modern app icon design, scalable vector style, suitable for iOS and Android, professional tech aesthetic, auto-generated ready'),
            array('title' => 'UI Placeholder Image', 'content' => 'Clean UI placeholder image, suitable for development mockups, professional interface aesthetic, various sizes'),
            array('title' => 'Dashboard Graphic', 'content' => 'Professional dashboard graphic element, data visualisation ready, suitable for SaaS applications, modern tech style'),
            array('title' => 'Error State Illustration', 'content' => 'Friendly error state illustration, user-friendly, suitable for 404 pages and error messages, not intimidating'),
            array('title' => 'Loading State Visual', 'content' => 'Engaging loading state visual, reduces perceived wait time, suitable for web applications, professional tech aesthetic'),
        ),
    ),
    'AI-Imagen: Educator' => array(
        'description' => 'Custom diagrams',
        'prompts' => array(
            array('title' => 'Custom Learning Diagram', 'content' => 'Clear custom learning diagram, educational focus, suitable for lesson plans and presentations, student-friendly'),
            array('title' => 'Concept Visualisation', 'content' => 'Educational concept visualisation, complex ideas simplified, suitable for teaching materials, professional academic style'),
            array('title' => 'Interactive Lesson Visual', 'content' => 'Engaging interactive lesson visual, suitable for digital learning platforms, encourages student participation'),
            array('title' => 'Study Guide Graphic', 'content' => 'Professional study guide graphic, clear information hierarchy, suitable for exam preparation materials'),
            array('title' => 'Classroom Display', 'content' => 'Large classroom display visual, readable from distance, suitable for wall posters and presentations'),
        ),
    ),
    'AI-Imagen: Event Planner' => array(
        'description' => 'Posters and invites',
        'prompts' => array(
            array('title' => 'Event Poster Design', 'content' => 'Professional event poster, clear event details space, eye-catching design, suitable for both print and digital promotion'),
            array('title' => 'Elegant Invitation', 'content' => 'Elegant event invitation, sophisticated aesthetic, suitable for formal events, customisable text areas'),
            array('title' => 'Festival Promotional', 'content' => 'Vibrant festival promotional poster, energetic and dynamic, bold typography, youth-oriented design'),
            array('title' => 'Corporate Event Visual', 'content' => 'Professional corporate event visual, business-appropriate, suitable for conferences and seminars, clean design'),
            array('title' => 'Save the Date Card', 'content' => 'Beautiful save the date card design, memorable aesthetic, suitable for weddings and special events'),
        ),
    ),


    // STYLES (9 categories)
    'AI-Imagen: Photorealistic' => array(
        'description' => 'DSLR, cinematic quality',
        'prompts' => array(
            array('title' => 'DSLR Photography Style', 'content' => 'Photorealistic DSLR photography style, professional camera quality, shallow depth of field, natural lighting, high resolution, cinematic composition'),
            array('title' => 'Cinematic Shot', 'content' => 'Cinematic quality photograph, film-like aesthetic, dramatic lighting, professional colour grading, movie production quality'),
            array('title' => 'Studio Photography', 'content' => 'Professional studio photography, controlled lighting, photorealistic detail, commercial photography quality, sharp focus'),
            array('title' => 'Natural Light Portrait', 'content' => 'Photorealistic natural light portrait, authentic feel, professional photography, DSLR quality, beautiful bokeh'),
            array('title' => 'Architectural Photography', 'content' => 'Photorealistic architectural photography, professional real estate style, HDR quality, sharp details, perfect perspective'),
        ),
    ),
    'AI-Imagen: Flat & Minimalist' => array(
        'description' => 'Clean, simple designs',
        'prompts' => array(
            array('title' => 'Flat Design Illustration', 'content' => 'Clean flat design illustration, minimalist aesthetic, simple shapes, limited colour palette, modern and professional'),
            array('title' => 'Minimalist Composition', 'content' => 'Minimalist composition, negative space emphasis, simple and elegant, clean lines, sophisticated simplicity'),
            array('title' => 'Geometric Flat Design', 'content' => 'Geometric flat design, simple shapes and patterns, modern minimalist aesthetic, clean and professional'),
            array('title' => 'Simple Icon Style', 'content' => 'Simple flat icon style, minimalist design, clean and recognisable, suitable for UI/UX, scalable'),
            array('title' => 'Clean Vector Art', 'content' => 'Clean vector art, flat design aesthetic, minimalist approach, professional and modern, simple colour scheme'),
        ),
    ),
    'AI-Imagen: Cartoon & Anime' => array(
        'description' => 'Illustrated characters',
        'prompts' => array(
            array('title' => 'Cartoon Character', 'content' => 'Cute cartoon character illustration, friendly and approachable, vibrant colours, suitable for children and family content'),
            array('title' => 'Anime Style Art', 'content' => 'Anime style illustration, Japanese animation aesthetic, expressive characters, detailed shading, manga-inspired'),
            array('title' => 'Comic Book Style', 'content' => 'Comic book style illustration, bold outlines, dynamic composition, action-packed, vibrant colours'),
            array('title' => 'Chibi Character', 'content' => 'Adorable chibi character design, super-deformed style, cute and playful, suitable for stickers and merchandise'),
            array('title' => 'Animated Series Style', 'content' => 'Animated TV series style, consistent character design, suitable for animation production, professional cartoon aesthetic'),
        ),
    ),
    'AI-Imagen: Digital Painting' => array(
        'description' => 'Fantasy, sci-fi art',
        'prompts' => array(
            array('title' => 'Fantasy Digital Painting', 'content' => 'Epic fantasy digital painting, detailed brushwork, magical atmosphere, concept art quality, suitable for book covers'),
            array('title' => 'Sci-Fi Concept Art', 'content' => 'Futuristic sci-fi concept art, detailed digital painting, advanced technology aesthetic, cinematic quality'),
            array('title' => 'Character Portrait', 'content' => 'Detailed character portrait, digital painting style, fantasy or sci-fi aesthetic, professional concept art quality'),
            array('title' => 'Environment Matte Painting', 'content' => 'Epic environment matte painting, detailed landscape, fantasy or sci-fi setting, cinematic composition'),
            array('title' => 'Creature Design', 'content' => 'Detailed creature design, digital painting style, fantasy or sci-fi aesthetic, concept art quality'),
        ),
    ),
    'AI-Imagen: Retro & Vintage' => array(
        'description' => 'Nostalgic aesthetics',
        'prompts' => array(
            array('title' => '80s Retro Style', 'content' => '1980s retro aesthetic, neon colours, synthwave style, nostalgic vibe, vintage technology feel'),
            array('title' => 'Vintage Poster', 'content' => 'Vintage poster design, aged paper texture, retro typography, nostalgic colour palette, classic advertising style'),
            array('title' => '50s Americana', 'content' => '1950s Americana style, mid-century modern aesthetic, vintage advertising, nostalgic and cheerful'),
            array('title' => 'Retro Futurism', 'content' => 'Retro futurism aesthetic, 1960s vision of the future, vintage sci-fi style, nostalgic technology'),
            array('title' => 'Vintage Photography', 'content' => 'Vintage photography style, aged photo aesthetic, nostalgic colour grading, classic film camera look'),
        ),
    ),
    'AI-Imagen: 3D & CGI' => array(
        'description' => 'Rendered, isometric',
        'prompts' => array(
            array('title' => '3D Rendered Scene', 'content' => 'Professional 3D rendered scene, photorealistic CGI, detailed textures, professional lighting, cinema 4D quality'),
            array('title' => 'Isometric Illustration', 'content' => 'Isometric 3D illustration, clean and modern, suitable for infographics and tech visualisations, professional render'),
            array('title' => 'Product Render', 'content' => 'Photorealistic 3D product render, studio lighting, perfect reflections, commercial quality CGI'),
            array('title' => 'Low Poly 3D', 'content' => 'Low poly 3D art style, geometric shapes, modern aesthetic, suitable for games and illustrations'),
            array('title' => 'Architectural Render', 'content' => 'Professional architectural 3D render, photorealistic quality, detailed materials, perfect lighting'),
        ),
    ),
    'AI-Imagen: Hand-drawn' => array(
        'description' => 'Watercolour, sketch',
        'prompts' => array(
            array('title' => 'Watercolour Painting', 'content' => 'Beautiful watercolour painting style, soft colours, artistic brushstrokes, traditional art aesthetic, elegant and delicate'),
            array('title' => 'Pencil Sketch', 'content' => 'Hand-drawn pencil sketch, artistic line work, traditional drawing style, authentic sketch aesthetic'),
            array('title' => 'Ink Drawing', 'content' => 'Hand-drawn ink illustration, bold line work, traditional pen and ink style, artistic and expressive'),
            array('title' => 'Charcoal Art', 'content' => 'Charcoal drawing style, dramatic shading, traditional art medium, expressive and moody'),
            array('title' => 'Pastel Illustration', 'content' => 'Soft pastel illustration, gentle colours, traditional art style, dreamy and artistic aesthetic'),
        ),
    ),
    'AI-Imagen: Brand Layouts' => array(
        'description' => 'Magazine, social banners',
        'prompts' => array(
            array('title' => 'Magazine Layout', 'content' => 'Professional magazine layout design, editorial aesthetic, clean typography, grid-based composition, publication-ready'),
            array('title' => 'Social Media Banner', 'content' => 'Brand-consistent social media banner, professional design, optimised dimensions, suitable for multiple platforms'),
            array('title' => 'Brand Template', 'content' => 'Professional brand template design, consistent visual identity, suitable for marketing materials, on-brand aesthetic'),
            array('title' => 'Editorial Spread', 'content' => 'Magazine editorial spread design, professional layout, balanced composition, suitable for print and digital'),
            array('title' => 'Marketing Collateral', 'content' => 'Brand-aligned marketing collateral, professional design system, consistent visual language, multi-purpose template'),
        ),
    ),
    'AI-Imagen: Transparent Assets' => array(
        'description' => 'Stickers, cut-outs',
        'prompts' => array(
            array('title' => 'Transparent Sticker', 'content' => 'Transparent background sticker design, clean cut-out, suitable for print-on-demand, vibrant colours, clear edges'),
            array('title' => 'PNG Cut-out', 'content' => 'Clean PNG cut-out with transparent background, perfect edges, suitable for graphic design, no background artifacts'),
            array('title' => 'Die-Cut Design', 'content' => 'Die-cut ready design, transparent background, clean outline, suitable for stickers and decals, print-ready'),
            array('title' => 'Isolated Object', 'content' => 'Isolated object on transparent background, clean extraction, suitable for compositing, professional cut-out'),
            array('title' => 'Transparent Icon', 'content' => 'Transparent background icon, clean edges, suitable for overlays and graphic design, scalable PNG'),
        ),
    ),
);

// Import the prompts
$imported_count = 0;
$skipped_count = 0;
$errors = array();

echo '<h1>AI-Imagen Sample Prompts Import</h1>';
echo '<p>Importing sample prompts into AI-Core Prompt Library...</p>';
echo '<hr>';

foreach ($sample_data as $group_name => $group_data) {
    echo '<h2>' . esc_html($group_name) . '</h2>';
    
    // Check if group exists
    $existing_groups = $prompt_library->get_groups();
    $group_id = null;
    
    foreach ($existing_groups as $group) {
        if ($group['name'] === $group_name) {
            $group_id = $group['id'];
            break;
        }
    }
    
    // Create group if it doesn't exist
    if (!$group_id) {
        $group_id = $prompt_library->save_group(array(
            'name' => $group_name,
            'description' => $group_data['description'],
        ));
        
        if ($group_id) {
            echo '<p style="color: green;">✓ Created group: ' . esc_html($group_name) . '</p>';
        } else {
            echo '<p style="color: red;">✗ Failed to create group: ' . esc_html($group_name) . '</p>';
            $errors[] = 'Failed to create group: ' . $group_name;
            continue;
        }
    } else {
        echo '<p style="color: blue;">→ Group already exists: ' . esc_html($group_name) . '</p>';
    }
    
    // Import prompts for this group
    foreach ($group_data['prompts'] as $prompt_data) {
        // Check if prompt already exists
        $existing_prompts = $prompt_library->get_prompts(array('group_id' => $group_id));
        $prompt_exists = false;
        
        foreach ($existing_prompts as $existing_prompt) {
            if ($existing_prompt['title'] === $prompt_data['title']) {
                $prompt_exists = true;
                break;
            }
        }
        
        if ($prompt_exists) {
            echo '<p style="color: orange; margin-left: 20px;">⊙ Skipped (already exists): ' . esc_html($prompt_data['title']) . '</p>';
            $skipped_count++;
            continue;
        }
        
        // Save the prompt
        $prompt_id = $prompt_library->save_prompt(array(
            'title' => $prompt_data['title'],
            'content' => $prompt_data['content'],
            'group_id' => $group_id,
            'type' => 'image',
            'provider' => '', // Empty = any provider
        ));
        
        if ($prompt_id) {
            echo '<p style="color: green; margin-left: 20px;">✓ Imported: ' . esc_html($prompt_data['title']) . '</p>';
            $imported_count++;
        } else {
            echo '<p style="color: red; margin-left: 20px;">✗ Failed: ' . esc_html($prompt_data['title']) . '</p>';
            $errors[] = 'Failed to import: ' . $prompt_data['title'];
        }
    }
    
    echo '<br>';
}

// Summary
echo '<hr>';
echo '<h2>Import Summary</h2>';
echo '<p><strong>Imported:</strong> ' . $imported_count . ' prompts</p>';
echo '<p><strong>Skipped:</strong> ' . $skipped_count . ' prompts (already exist)</p>';

if (!empty($errors)) {
    echo '<p><strong style="color: red;">Errors:</strong> ' . count($errors) . '</p>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li style="color: red;">' . esc_html($error) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color: green;"><strong>✓ Import completed successfully!</strong></p>';
}

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=ai-core-prompt-library') . '" class="button button-primary">View Prompt Library</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=ai-imagen') . '" class="button">Go to AI-Imagen</a></p>';
echo '<p style="color: #666; font-size: 12px;"><em>Note: You can safely delete this file (import-sample-prompts.php) after running it once.</em></p>';

