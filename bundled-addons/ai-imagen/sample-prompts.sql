-- Sample AI-Imagen Prompts for Prompt Library
-- Import these into the AI-Core Prompt Library to populate categories
-- These prompts are organised by Use Case, Role, and Style

-- Note: Replace 'wp_' with your actual WordPress table prefix if different
-- Run this SQL in phpMyAdmin or via WP-CLI: wp db query < sample-prompts.sql

-- First, create the groups
INSERT INTO `wp_ai_core_prompt_groups` (`name`, `description`, `created_at`) VALUES
('AI-Imagen: Marketing', 'Marketing and advertising image prompts', NOW()),
('AI-Imagen: Social Media', 'Social media content image prompts', NOW()),
('AI-Imagen: Product Photography', 'Product photography image prompts', NOW()),
('AI-Imagen: Website Design', 'Website and web design image prompts', NOW()),
('AI-Imagen: Event Planning', 'Event planning and invitation image prompts', NOW()),
('AI-Imagen: Education', 'Educational content image prompts', NOW()),
('AI-Imagen: Business', 'Small business image prompts', NOW()),
('AI-Imagen: Design', 'Graphic design image prompts', NOW()),
('AI-Imagen: Content Publishing', 'Content and publishing image prompts', NOW()),
('AI-Imagen: Developer', 'Developer and tech image prompts', NOW());

-- Get the group IDs (you'll need to adjust these based on your actual IDs)
-- For this example, we'll use variables

-- Marketing Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Professional Product Ad', 'Professional product advertisement photo on white background, studio lighting, high-end commercial photography style, sharp focus, clean composition', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Marketing' LIMIT 1), 'image', '', NOW()),
('Brand Hero Image', 'Modern brand hero image with bold typography, vibrant colours, professional corporate style, suitable for website banner or social media header', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Marketing' LIMIT 1), 'image', '', NOW()),
('Email Campaign Visual', 'Eye-catching email campaign visual, promotional style, bright and engaging, clear focal point, suitable for newsletter header', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Marketing' LIMIT 1), 'image', '', NOW()),
('Billboard Advertisement', 'Large-scale billboard advertisement design, bold and simple, high contrast, readable from distance, impactful visual message', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Marketing' LIMIT 1), 'image', '', NOW()),
('Print Ad Layout', 'Magazine print advertisement layout, professional photography, elegant typography, luxury brand aesthetic, high-quality finish', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Marketing' LIMIT 1), 'image', '', NOW());

-- Social Media Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Instagram Post', 'Vibrant Instagram post graphic, trendy aesthetic, bright colours, engaging composition, optimised for mobile viewing, square format', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Social Media' LIMIT 1), 'image', '', NOW()),
('Facebook Cover Photo', 'Professional Facebook cover photo, wide panoramic format, brand-focused, clean design, suitable for business page header', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Social Media' LIMIT 1), 'image', '', NOW()),
('Twitter Header', 'Modern Twitter/X header image, minimalist design, professional branding, optimised dimensions, clean and contemporary', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Social Media' LIMIT 1), 'image', '', NOW()),
('LinkedIn Post Visual', 'Professional LinkedIn post visual, corporate aesthetic, business-appropriate, clean and polished, suitable for B2B content', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Social Media' LIMIT 1), 'image', '', NOW()),
('Social Media Story', 'Engaging social media story graphic, vertical format, bold text overlay space, attention-grabbing, mobile-optimised', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Social Media' LIMIT 1), 'image', '', NOW());

-- Product Photography Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('White Background Product', 'Professional product photo on pure white background, studio lighting, e-commerce style, sharp focus, no shadows, clean and minimal', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Product Photography' LIMIT 1), 'image', '', NOW()),
('Lifestyle Product Shot', 'Lifestyle product photography, natural setting, authentic feel, soft lighting, product in use, relatable context', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Product Photography' LIMIT 1), 'image', '', NOW()),
('Flat Lay Product', 'Flat lay product photography, overhead view, styled composition, complementary props, Instagram-worthy aesthetic', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Product Photography' LIMIT 1), 'image', '', NOW()),
('Luxury Product Shot', 'Luxury product photography, dramatic lighting, premium feel, elegant composition, high-end commercial style', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Product Photography' LIMIT 1), 'image', '', NOW()),
('Product Detail Close-up', 'Macro product detail shot, extreme close-up, texture emphasis, professional lighting, showcasing craftsmanship and quality', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Product Photography' LIMIT 1), 'image', '', NOW());

-- Website Design Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Website Hero Image', 'Modern website hero section background, abstract geometric patterns, professional corporate colours, suitable for tech company', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Website Design' LIMIT 1), 'image', '', NOW()),
('Landing Page Visual', 'Engaging landing page hero visual, conversion-focused, clear value proposition space, professional and trustworthy aesthetic', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Website Design' LIMIT 1), 'image', '', NOW()),
('About Page Background', 'Warm and welcoming about page background, team-focused, professional yet approachable, suitable for company culture showcase', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Website Design' LIMIT 1), 'image', '', NOW()),
('Blog Header Image', 'Clean blog post header image, minimalist design, reading-friendly, professional publishing aesthetic, suitable for article hero', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Website Design' LIMIT 1), 'image', '', NOW()),
('Contact Page Visual', 'Professional contact page background, inviting and accessible, business-appropriate, encourages communication', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Website Design' LIMIT 1), 'image', '', NOW());

-- Event Planning Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Wedding Invitation', 'Elegant wedding invitation design, romantic aesthetic, floral elements, sophisticated typography, timeless and beautiful', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Event Planning' LIMIT 1), 'image', '', NOW()),
('Corporate Event Poster', 'Professional corporate event poster, modern business aesthetic, clear information hierarchy, suitable for conference or seminar', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Event Planning' LIMIT 1), 'image', '', NOW()),
('Birthday Party Invite', 'Fun and colourful birthday party invitation, celebratory feel, playful design, suitable for both kids and adults', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Event Planning' LIMIT 1), 'image', '', NOW()),
('Gala Dinner Invitation', 'Luxurious gala dinner invitation, black-tie aesthetic, elegant and sophisticated, premium feel, formal event style', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Event Planning' LIMIT 1), 'image', '', NOW()),
('Festival Poster', 'Vibrant music festival poster, energetic and dynamic, bold typography, eye-catching colours, youth-oriented design', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Event Planning' LIMIT 1), 'image', '', NOW());

-- Education Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Educational Infographic', 'Clear educational infographic background, learning-focused, organised layout, suitable for teaching materials, professional academic style', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Education' LIMIT 1), 'image', '', NOW()),
('Course Thumbnail', 'Engaging online course thumbnail, professional educational aesthetic, clear subject indication, suitable for e-learning platform', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Education' LIMIT 1), 'image', '', NOW()),
('Presentation Slide', 'Professional presentation slide background, clean and minimal, suitable for academic or business presentation, non-distracting', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Education' LIMIT 1), 'image', '', NOW()),
('Worksheet Header', 'Friendly educational worksheet header, student-appropriate, encouraging learning environment, suitable for classroom materials', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Education' LIMIT 1), 'image', '', NOW()),
('Certificate Background', 'Formal certificate background, professional achievement aesthetic, elegant borders, suitable for awards and recognition', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Education' LIMIT 1), 'image', '', NOW());

-- Business Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Business Card Design', 'Professional business card design, modern corporate aesthetic, clean layout, suitable for small business owner, memorable', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Business' LIMIT 1), 'image', '', NOW()),
('Storefront Sign', 'Attractive storefront sign design, welcoming and professional, clear branding, suitable for retail or service business', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Business' LIMIT 1), 'image', '', NOW()),
('Invoice Header', 'Professional invoice header design, business-appropriate, clean and organised, suitable for small business documentation', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Business' LIMIT 1), 'image', '', NOW()),
('Promotional Flyer', 'Eye-catching promotional flyer, small business focused, clear call-to-action, suitable for local marketing campaign', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Business' LIMIT 1), 'image', '', NOW()),
('Menu Design', 'Attractive restaurant menu design, appetising aesthetic, clear typography, professional food service presentation', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Business' LIMIT 1), 'image', '', NOW());

-- Design Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Logo Concept', 'Modern logo design concept, minimalist and memorable, scalable vector style, professional brand identity, timeless aesthetic', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Design' LIMIT 1), 'image', '', NOW()),
('Brand Colour Palette', 'Cohesive brand colour palette visualisation, harmonious colour scheme, professional presentation, suitable for brand guidelines', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Design' LIMIT 1), 'image', '', NOW()),
('Icon Set', 'Consistent icon set design, minimalist line style, uniform sizing, professional UI/UX aesthetic, scalable and clear', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Design' LIMIT 1), 'image', '', NOW()),
('Pattern Design', 'Seamless pattern design, repeatable tile, modern aesthetic, suitable for backgrounds or textile, visually interesting', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Design' LIMIT 1), 'image', '', NOW()),
('Typography Poster', 'Bold typography poster design, impactful message, modern font pairing, suitable for motivational or promotional use', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Design' LIMIT 1), 'image', '', NOW());

-- Content Publishing Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('Book Cover', 'Professional book cover design, genre-appropriate aesthetic, eye-catching composition, suitable for self-publishing, marketable', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Content Publishing' LIMIT 1), 'image', '', NOW()),
('Magazine Layout', 'Modern magazine layout design, editorial aesthetic, clean typography, professional publishing standard, visually engaging', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Content Publishing' LIMIT 1), 'image', '', NOW()),
('eBook Cover', 'Digital eBook cover design, thumbnail-optimised, clear title visibility, professional self-publishing aesthetic', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Content Publishing' LIMIT 1), 'image', '', NOW()),
('Newsletter Header', 'Engaging newsletter header design, brand-consistent, suitable for email publishing, professional and welcoming', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Content Publishing' LIMIT 1), 'image', '', NOW()),
('Article Featured Image', 'Compelling article featured image, blog-appropriate, SEO-friendly, professional publishing aesthetic, shareable on social media', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Content Publishing' LIMIT 1), 'image', '', NOW());

-- Developer Prompts
INSERT INTO `wp_ai_core_prompts` (`title`, `content`, `group_id`, `type`, `provider`, `created_at`) VALUES
('App Icon', 'Modern mobile app icon design, minimalist and recognisable, suitable for iOS and Android, scalable, professional tech aesthetic', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Developer' LIMIT 1), 'image', '', NOW()),
('Dashboard UI', 'Clean dashboard UI background, modern SaaS aesthetic, data-focused, professional tech interface, suitable for web application', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Developer' LIMIT 1), 'image', '', NOW()),
('Error Page Illustration', 'Friendly 404 error page illustration, user-friendly, not intimidating, modern tech aesthetic, suitable for web application', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Developer' LIMIT 1), 'image', '', NOW()),
('Loading Screen', 'Engaging loading screen design, modern tech aesthetic, brand-appropriate, reduces perceived wait time, professional', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Developer' LIMIT 1), 'image', '', NOW()),
('API Documentation Header', 'Professional API documentation header, developer-focused, clean and technical, suitable for technical documentation', (SELECT id FROM wp_ai_core_prompt_groups WHERE name = 'AI-Imagen: Developer' LIMIT 1), 'image', '', NOW());

-- Instructions for use:
-- 1. Replace 'wp_' with your actual WordPress table prefix
-- 2. Run this SQL in phpMyAdmin or via WP-CLI
-- 3. Verify the prompts appear in AI-Core > Prompt Library
-- 4. Test the prompts in AI-Imagen by selecting workflow categories

