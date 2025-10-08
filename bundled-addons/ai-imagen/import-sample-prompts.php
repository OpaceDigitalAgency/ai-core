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
    'AI-Imagen: Marketing' => array(
        'description' => 'Marketing and advertising image prompts',
        'prompts' => array(
            array('title' => 'Professional Product Ad', 'content' => 'Professional product advertisement photo on white background, studio lighting, high-end commercial photography style, sharp focus, clean composition'),
            array('title' => 'Brand Hero Image', 'content' => 'Modern brand hero image with bold typography, vibrant colours, professional corporate style, suitable for website banner or social media header'),
            array('title' => 'Email Campaign Visual', 'content' => 'Eye-catching email campaign visual, promotional style, bright and engaging, clear focal point, suitable for newsletter header'),
            array('title' => 'Billboard Advertisement', 'content' => 'Large-scale billboard advertisement design, bold and simple, high contrast, readable from distance, impactful visual message'),
            array('title' => 'Print Ad Layout', 'content' => 'Magazine print advertisement layout, professional photography, elegant typography, luxury brand aesthetic, high-quality finish'),
        ),
    ),
    'AI-Imagen: Social Media' => array(
        'description' => 'Social media content image prompts',
        'prompts' => array(
            array('title' => 'Instagram Post', 'content' => 'Vibrant Instagram post graphic, trendy aesthetic, bright colours, engaging composition, optimised for mobile viewing, square format'),
            array('title' => 'Facebook Cover Photo', 'content' => 'Professional Facebook cover photo, wide panoramic format, brand-focused, clean design, suitable for business page header'),
            array('title' => 'Twitter Header', 'content' => 'Modern Twitter/X header image, minimalist design, professional branding, optimised dimensions, clean and contemporary'),
            array('title' => 'LinkedIn Post Visual', 'content' => 'Professional LinkedIn post visual, corporate aesthetic, business-appropriate, clean and polished, suitable for B2B content'),
            array('title' => 'Social Media Story', 'content' => 'Engaging social media story graphic, vertical format, bold text overlay space, attention-grabbing, mobile-optimised'),
        ),
    ),
    'AI-Imagen: Product Photography' => array(
        'description' => 'Product photography image prompts',
        'prompts' => array(
            array('title' => 'White Background Product', 'content' => 'Professional product photo on pure white background, studio lighting, e-commerce style, sharp focus, no shadows, clean and minimal'),
            array('title' => 'Lifestyle Product Shot', 'content' => 'Lifestyle product photography, natural setting, authentic feel, soft lighting, product in use, relatable context'),
            array('title' => 'Flat Lay Product', 'content' => 'Flat lay product photography, overhead view, styled composition, complementary props, Instagram-worthy aesthetic'),
            array('title' => 'Luxury Product Shot', 'content' => 'Luxury product photography, dramatic lighting, premium feel, elegant composition, high-end commercial style'),
            array('title' => 'Product Detail Close-up', 'content' => 'Macro product detail shot, extreme close-up, texture emphasis, professional lighting, showcasing craftsmanship and quality'),
        ),
    ),
    'AI-Imagen: Website Design' => array(
        'description' => 'Website and web design image prompts',
        'prompts' => array(
            array('title' => 'Website Hero Image', 'content' => 'Modern website hero section background, abstract geometric patterns, professional corporate colours, suitable for tech company'),
            array('title' => 'Landing Page Visual', 'content' => 'Engaging landing page hero visual, conversion-focused, clear value proposition space, professional and trustworthy aesthetic'),
            array('title' => 'About Page Background', 'content' => 'Warm and welcoming about page background, team-focused, professional yet approachable, suitable for company culture showcase'),
            array('title' => 'Blog Header Image', 'content' => 'Clean blog post header image, minimalist design, reading-friendly, professional publishing aesthetic, suitable for article hero'),
            array('title' => 'Contact Page Visual', 'content' => 'Professional contact page background, inviting and accessible, business-appropriate, encourages communication'),
        ),
    ),
    'AI-Imagen: Event Planning' => array(
        'description' => 'Event planning and invitation image prompts',
        'prompts' => array(
            array('title' => 'Wedding Invitation', 'content' => 'Elegant wedding invitation design, romantic aesthetic, floral elements, sophisticated typography, timeless and beautiful'),
            array('title' => 'Corporate Event Poster', 'content' => 'Professional corporate event poster, modern business aesthetic, clear information hierarchy, suitable for conference or seminar'),
            array('title' => 'Birthday Party Invite', 'content' => 'Fun and colourful birthday party invitation, celebratory feel, playful design, suitable for both kids and adults'),
            array('title' => 'Gala Dinner Invitation', 'content' => 'Luxurious gala dinner invitation, black-tie aesthetic, elegant and sophisticated, premium feel, formal event style'),
            array('title' => 'Festival Poster', 'content' => 'Vibrant music festival poster, energetic and dynamic, bold typography, eye-catching colours, youth-oriented design'),
        ),
    ),
    'AI-Imagen: Education' => array(
        'description' => 'Educational content image prompts',
        'prompts' => array(
            array('title' => 'Educational Infographic', 'content' => 'Clear educational infographic background, learning-focused, organised layout, suitable for teaching materials, professional academic style'),
            array('title' => 'Course Thumbnail', 'content' => 'Engaging online course thumbnail, professional educational aesthetic, clear subject indication, suitable for e-learning platform'),
            array('title' => 'Presentation Slide', 'content' => 'Professional presentation slide background, clean and minimal, suitable for academic or business presentation, non-distracting'),
            array('title' => 'Worksheet Header', 'content' => 'Friendly educational worksheet header, student-appropriate, encouraging learning environment, suitable for classroom materials'),
            array('title' => 'Certificate Background', 'content' => 'Formal certificate background, professional achievement aesthetic, elegant borders, suitable for awards and recognition'),
        ),
    ),
    'AI-Imagen: Business' => array(
        'description' => 'Small business image prompts',
        'prompts' => array(
            array('title' => 'Business Card Design', 'content' => 'Professional business card design, modern corporate aesthetic, clean layout, suitable for small business owner, memorable'),
            array('title' => 'Storefront Sign', 'content' => 'Attractive storefront sign design, welcoming and professional, clear branding, suitable for retail or service business'),
            array('title' => 'Invoice Header', 'content' => 'Professional invoice header design, business-appropriate, clean and organised, suitable for small business documentation'),
            array('title' => 'Promotional Flyer', 'content' => 'Eye-catching promotional flyer, small business focused, clear call-to-action, suitable for local marketing campaign'),
            array('title' => 'Menu Design', 'content' => 'Attractive restaurant menu design, appetising aesthetic, clear typography, professional food service presentation'),
        ),
    ),
    'AI-Imagen: Design' => array(
        'description' => 'Graphic design image prompts',
        'prompts' => array(
            array('title' => 'Logo Concept', 'content' => 'Modern logo design concept, minimalist and memorable, scalable vector style, professional brand identity, timeless aesthetic'),
            array('title' => 'Brand Colour Palette', 'content' => 'Cohesive brand colour palette visualisation, harmonious colour scheme, professional presentation, suitable for brand guidelines'),
            array('title' => 'Icon Set', 'content' => 'Consistent icon set design, minimalist line style, uniform sizing, professional UI/UX aesthetic, scalable and clear'),
            array('title' => 'Pattern Design', 'content' => 'Seamless pattern design, repeatable tile, modern aesthetic, suitable for backgrounds or textile, visually interesting'),
            array('title' => 'Typography Poster', 'content' => 'Bold typography poster design, impactful message, modern font pairing, suitable for motivational or promotional use'),
        ),
    ),
    'AI-Imagen: Content Publishing' => array(
        'description' => 'Content and publishing image prompts',
        'prompts' => array(
            array('title' => 'Book Cover', 'content' => 'Professional book cover design, genre-appropriate aesthetic, eye-catching composition, suitable for self-publishing, marketable'),
            array('title' => 'Magazine Layout', 'content' => 'Modern magazine layout design, editorial aesthetic, clean typography, professional publishing standard, visually engaging'),
            array('title' => 'eBook Cover', 'content' => 'Digital eBook cover design, thumbnail-optimised, clear title visibility, professional self-publishing aesthetic'),
            array('title' => 'Newsletter Header', 'content' => 'Engaging newsletter header design, brand-consistent, suitable for email publishing, professional and welcoming'),
            array('title' => 'Article Featured Image', 'content' => 'Compelling article featured image, blog-appropriate, SEO-friendly, professional publishing aesthetic, shareable on social media'),
        ),
    ),
    'AI-Imagen: Developer' => array(
        'description' => 'Developer and tech image prompts',
        'prompts' => array(
            array('title' => 'App Icon', 'content' => 'Modern mobile app icon design, minimalist and recognisable, suitable for iOS and Android, scalable, professional tech aesthetic'),
            array('title' => 'Dashboard UI', 'content' => 'Clean dashboard UI background, modern SaaS aesthetic, data-focused, professional tech interface, suitable for web application'),
            array('title' => 'Error Page Illustration', 'content' => 'Friendly 404 error page illustration, user-friendly, not intimidating, modern tech aesthetic, suitable for web application'),
            array('title' => 'Loading Screen', 'content' => 'Engaging loading screen design, modern tech aesthetic, brand-appropriate, reduces perceived wait time, professional'),
            array('title' => 'API Documentation Header', 'content' => 'Professional API documentation header, developer-focused, clean and technical, suitable for technical documentation'),
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

