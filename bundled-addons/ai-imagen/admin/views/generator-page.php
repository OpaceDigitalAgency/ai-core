<?php
/**
 * AI-Imagen Generator Page
 * 
 * Main image generation interface
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$generator = AI_Imagen_Generator::get_instance();
$providers = $generator->get_available_providers();
$settings = AI_Imagen_Settings::get_instance();
$media = AI_Imagen_Media::get_instance();
$remaining = $media->get_remaining_count();
?>

<div class="wrap ai-imagen-generator">
    <h1><?php esc_html_e('AI Image Generation', 'ai-imagen'); ?></h1>
    
    <?php if ($remaining !== 'unlimited'): ?>
        <div class="ai-imagen-limit-notice">
            <span class="dashicons dashicons-info"></span>
            <?php
            printf(
                esc_html__('Remaining generations today: %s', 'ai-imagen'),
                '<strong>' . esc_html($remaining) . '</strong>'
            );
            ?>
        </div>
    <?php endif; ?>
    
    <div class="ai-imagen-container">
        <!-- Left Panel: Generation Controls -->
        <div class="ai-imagen-controls">
            
            <!-- Workflow Selection -->
            <div class="ai-imagen-section ai-imagen-workflow-section">
                <h2><?php esc_html_e('Choose Your Workflow', 'ai-imagen'); ?></h2>
                <p class="workflow-description"><?php esc_html_e('Select a workflow to get started with optimised templates and settings', 'ai-imagen'); ?></p>
                <div class="ai-imagen-workflow-tabs">
                    <button type="button" class="workflow-tab active" data-workflow="just-start">
                        <span class="workflow-icon">⚡</span>
                        <span class="workflow-label"><?php esc_html_e('Just Start', 'ai-imagen'); ?></span>
                        <span class="workflow-desc"><?php esc_html_e('Quick & Simple', 'ai-imagen'); ?></span>
                    </button>
                    <button type="button" class="workflow-tab" data-workflow="use-case">
                        <span class="workflow-icon">🎯</span>
                        <span class="workflow-label"><?php esc_html_e('Use Case', 'ai-imagen'); ?></span>
                        <span class="workflow-desc"><?php esc_html_e('By Purpose', 'ai-imagen'); ?></span>
                    </button>
                    <button type="button" class="workflow-tab" data-workflow="role">
                        <span class="workflow-icon">👤</span>
                        <span class="workflow-label"><?php esc_html_e('Role', 'ai-imagen'); ?></span>
                        <span class="workflow-desc"><?php esc_html_e('By Profession', 'ai-imagen'); ?></span>
                    </button>
                    <button type="button" class="workflow-tab" data-workflow="style">
                        <span class="workflow-icon">🎨</span>
                        <span class="workflow-label"><?php esc_html_e('Style', 'ai-imagen'); ?></span>
                        <span class="workflow-desc"><?php esc_html_e('By Aesthetic', 'ai-imagen'); ?></span>
                    </button>
                </div>
            </div>
            
            <!-- Workflow Content Panels -->
            <div class="ai-imagen-workflow-content">
                
                <!-- Just Start Panel -->
                <div class="workflow-panel active" id="panel-just-start">
                    <p class="description"><?php esc_html_e('Jump straight into creating your image with a simple prompt.', 'ai-imagen'); ?></p>
                </div>
                
                <!-- Use Case Panel -->
                <div class="workflow-panel" id="panel-use-case">
                    <p class="description"><?php esc_html_e('Select your use case for optimised templates and settings.', 'ai-imagen'); ?></p>
                    <div class="ai-imagen-cards">
                        <button type="button" class="ai-imagen-card" data-value="marketing-ads">
                            <span class="dashicons dashicons-megaphone"></span>
                            <h3><?php esc_html_e('Marketing & Ads', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Campaign banners, product shots', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="social-media">
                            <span class="dashicons dashicons-share"></span>
                            <h3><?php esc_html_e('Social Media', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Posts, stories, thumbnails', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="product-photography">
                            <span class="dashicons dashicons-camera"></span>
                            <h3><?php esc_html_e('Product Photography', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Professional product images', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="website-design">
                            <span class="dashicons dashicons-desktop"></span>
                            <h3><?php esc_html_e('Website Design', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Hero images, illustrations', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="publishing">
                            <span class="dashicons dashicons-book"></span>
                            <h3><?php esc_html_e('Publishing', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Article headers, covers', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="presentations">
                            <span class="dashicons dashicons-slides"></span>
                            <h3><?php esc_html_e('Presentations', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Slide backgrounds, diagrams', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="game-development">
                            <span class="dashicons dashicons-games"></span>
                            <h3><?php esc_html_e('Game Development', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Concept art, sprites', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="education">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <h3><?php esc_html_e('Education', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Diagrams, flashcards', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="print-on-demand">
                            <span class="dashicons dashicons-products"></span>
                            <h3><?php esc_html_e('Print-on-Demand', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('T-shirts, stickers, posters', 'ai-imagen'); ?></p>
                        </button>
                    </div>
                </div>
                
                <!-- Role Panel -->
                <div class="workflow-panel" id="panel-role">
                    <p class="description"><?php esc_html_e('Select your role for personalised optimisations.', 'ai-imagen'); ?></p>
                    <div class="ai-imagen-cards">
                        <button type="button" class="ai-imagen-card" data-value="marketing-manager">
                            <span class="dashicons dashicons-chart-line"></span>
                            <h3><?php esc_html_e('Marketing Manager', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Fast, on-brand campaigns', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="social-media-manager">
                            <span class="dashicons dashicons-twitter"></span>
                            <h3><?php esc_html_e('Social Media Manager', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Trending visuals instantly', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="small-business-owner">
                            <span class="dashicons dashicons-store"></span>
                            <h3><?php esc_html_e('Small Business Owner', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('DIY product photography', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="graphic-designer">
                            <span class="dashicons dashicons-art"></span>
                            <h3><?php esc_html_e('Graphic Designer', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Rapid ideation tools', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="content-publisher">
                            <span class="dashicons dashicons-media-document"></span>
                            <h3><?php esc_html_e('Content Publisher', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Editorial art, covers', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="developer">
                            <span class="dashicons dashicons-editor-code"></span>
                            <h3><?php esc_html_e('Developer', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Auto-generated assets', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="educator">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <h3><?php esc_html_e('Educator', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Custom diagrams', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="event-planner">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <h3><?php esc_html_e('Event Planner', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Posters and invites', 'ai-imagen'); ?></p>
                        </button>
                    </div>
                </div>
                
                <!-- Style Panel -->
                <div class="workflow-panel" id="panel-style">
                    <p class="description"><?php esc_html_e('Choose your visual style for consistent results.', 'ai-imagen'); ?></p>
                    <div class="ai-imagen-cards">
                        <button type="button" class="ai-imagen-card" data-value="photorealistic">
                            <span class="dashicons dashicons-camera-alt"></span>
                            <h3><?php esc_html_e('Photorealistic', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('DSLR, cinematic quality', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="flat-minimalist">
                            <span class="dashicons dashicons-minus"></span>
                            <h3><?php esc_html_e('Flat & Minimalist', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Clean, simple designs', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="cartoon-anime">
                            <span class="dashicons dashicons-smiley"></span>
                            <h3><?php esc_html_e('Cartoon & Anime', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Illustrated characters', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="digital-painting">
                            <span class="dashicons dashicons-art"></span>
                            <h3><?php esc_html_e('Digital Painting', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Fantasy, sci-fi art', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="retro-vintage">
                            <span class="dashicons dashicons-clock"></span>
                            <h3><?php esc_html_e('Retro & Vintage', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Nostalgic aesthetics', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="3d-cgi">
                            <span class="dashicons dashicons-admin-customizer"></span>
                            <h3><?php esc_html_e('3D & CGI', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Rendered, isometric', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="hand-drawn">
                            <span class="dashicons dashicons-edit"></span>
                            <h3><?php esc_html_e('Hand-drawn', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Watercolour, sketch', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="brand-layouts">
                            <span class="dashicons dashicons-layout"></span>
                            <h3><?php esc_html_e('Brand Layouts', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Magazine, social banners', 'ai-imagen'); ?></p>
                        </button>
                        <button type="button" class="ai-imagen-card" data-value="transparent">
                            <span class="dashicons dashicons-image-filter"></span>
                            <h3><?php esc_html_e('Transparent Assets', 'ai-imagen'); ?></h3>
                            <p><?php esc_html_e('Stickers, cut-outs', 'ai-imagen'); ?></p>
                        </button>
                    </div>
                </div>

                <!-- Prompt Suggestions (appears when workflow card is clicked) -->
                <div class="ai-imagen-prompt-suggestions" id="ai-imagen-prompt-suggestions" style="display: none;">
                    <h3><?php esc_html_e('Suggested Prompts', 'ai-imagen'); ?></h3>
                    <div class="prompt-suggestions-list" id="ai-imagen-prompt-suggestions-list">
                        <!-- Prompts will be loaded here dynamically -->
                    </div>
                </div>

            </div>

            <!-- Prompt Input -->
            <div class="ai-imagen-section">
                <h2><?php esc_html_e('Describe Your Image', 'ai-imagen'); ?></h2>
                
                <div class="ai-imagen-prompt-field">
                    <label for="ai-imagen-prompt"><?php esc_html_e('Describe Your Image', 'ai-imagen'); ?></label>
                    <textarea id="ai-imagen-prompt" rows="5" placeholder="<?php esc_attr_e('Describe the image you want to generate in detail...', 'ai-imagen'); ?>"></textarea>
                    <div class="prompt-actions">
                        <?php if ($settings->get('enable_prompt_enhancement', true)): ?>
                            <button type="button" class="button" id="ai-imagen-enhance-prompt">
                                <span class="dashicons dashicons-lightbulb"></span>
                                <?php esc_html_e('Enhance with AI', 'ai-imagen'); ?>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="button" id="ai-imagen-load-from-library">
                            <span class="dashicons dashicons-book-alt"></span>
                            <?php esc_html_e('Load from Library', 'ai-imagen'); ?>
                        </button>
                    </div>
                </div>

                <!-- Quick Start Ideas -->
                <div class="ai-imagen-quick-ideas">
                    <h3><?php esc_html_e('Quick Start Ideas', 'ai-imagen'); ?></h3>
                    <div class="quick-ideas-list">
                        <button type="button" class="quick-idea-btn"><?php esc_html_e('Professional product photo on white background', 'ai-imagen'); ?></button>
                        <button type="button" class="quick-idea-btn"><?php esc_html_e('Modern minimalist website hero image', 'ai-imagen'); ?></button>
                        <button type="button" class="quick-idea-btn"><?php esc_html_e('Vibrant social media post graphic', 'ai-imagen'); ?></button>
                        <button type="button" class="quick-idea-btn"><?php esc_html_e('Abstract background pattern for presentations', 'ai-imagen'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Prompt Preview (Collapsible) -->
            <div class="ai-imagen-section ai-imagen-prompt-preview-section">
                <div class="prompt-preview-header">
                    <button type="button" class="button button-link prompt-preview-toggle" id="ai-imagen-prompt-preview-toggle">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('View Generated Prompt', 'ai-imagen'); ?>
                        <span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
                    </button>
                </div>
                <div class="prompt-preview-content" id="ai-imagen-prompt-preview-content" style="display: none;">
                    <div class="prompt-preview-box">
                        <div class="prompt-preview-header-row">
                            <h4>
                                <?php esc_html_e('Complete Prompt', 'ai-imagen'); ?>
                                <span class="prompt-auto-badge"><?php esc_html_e('Auto-Generated', 'ai-imagen'); ?></span>
                            </h4>
                            <div class="prompt-preview-actions">
                                <button type="button" class="button button-small" id="ai-imagen-copy-prompt" title="<?php esc_attr_e('Copy to clipboard', 'ai-imagen'); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php esc_html_e('Copy', 'ai-imagen'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="prompt-preview-text" id="ai-imagen-prompt-preview-text">
                            <em><?php esc_html_e('Your final prompt will appear here as you make selections...', 'ai-imagen'); ?></em>
                        </div>
                    </div>
                    <div class="prompt-advanced-options">
                        <label class="prompt-manual-edit-toggle">
                            <input type="checkbox" id="ai-imagen-manual-edit-toggle">
                            <span><?php esc_html_e('Allow manual edits (will override auto-generation)', 'ai-imagen'); ?></span>
                        </label>
                        <div class="prompt-manual-edit-area" id="ai-imagen-manual-edit-area" style="display: none;">
                            <textarea id="ai-imagen-manual-prompt" rows="4" placeholder="<?php esc_attr_e('Edit the prompt manually...', 'ai-imagen'); ?>"></textarea>
                        </div>
                    </div>
                    <p class="description">
                        <?php esc_html_e('This shows the complete prompt that will be sent to the AI, including your main prompt, workflow selections, and scene builder elements.', 'ai-imagen'); ?>
                    </p>
                </div>
            </div>

            <!-- Scene Builder -->
            <?php if ($settings->get('enable_scene_builder', true)): ?>
                <div id="ai-imagen-scene-builder"></div>
            <?php endif; ?>

            <!-- Generation Settings -->
            <div class="ai-imagen-section ai-imagen-settings-section">
                <h2><?php esc_html_e('Generation Settings', 'ai-imagen'); ?></h2>
                
                <div class="ai-imagen-settings-grid">
                    <div class="setting-group">
                        <label for="ai-imagen-provider"><?php esc_html_e('Provider', 'ai-imagen'); ?></label>
                        <select id="ai-imagen-provider">
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?php echo esc_attr($provider); ?>">
                                    <?php echo esc_html(ucfirst($provider)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="setting-group">
                        <label for="ai-imagen-model"><?php esc_html_e('Model', 'ai-imagen'); ?></label>
                        <select id="ai-imagen-model">
                            <option value=""><?php esc_html_e('Loading...', 'ai-imagen'); ?></option>
                        </select>
                    </div>
                    
                    <div class="setting-group">
                        <label for="ai-imagen-quality"><?php esc_html_e('Quality', 'ai-imagen'); ?></label>
                        <select id="ai-imagen-quality">
                            <option value="standard"><?php esc_html_e('Standard', 'ai-imagen'); ?></option>
                            <option value="hd"><?php esc_html_e('HD', 'ai-imagen'); ?></option>
                        </select>
                    </div>
                    
                    <div class="setting-group">
                        <label for="ai-imagen-aspect-ratio"><?php esc_html_e('Aspect Ratio', 'ai-imagen'); ?></label>
                        <select id="ai-imagen-aspect-ratio">
                            <option value="1:1"><?php esc_html_e('1:1 (Square)', 'ai-imagen'); ?></option>
                            <option value="4:3"><?php esc_html_e('4:3 (Landscape)', 'ai-imagen'); ?></option>
                            <option value="16:9"><?php esc_html_e('16:9 (Widescreen)', 'ai-imagen'); ?></option>
                            <option value="9:16"><?php esc_html_e('9:16 (Portrait)', 'ai-imagen'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Generate Button -->
            <div class="ai-imagen-generate-section">
                <button type="button" class="button button-primary button-hero" id="ai-imagen-generate-btn">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <?php esc_html_e('Generate Image', 'ai-imagen'); ?>
                </button>
            </div>
            
        </div>
        
        <!-- Right Panel: Preview -->
        <div class="ai-imagen-preview">
            <div class="preview-header">
                <div class="preview-header-content">
                    <h2><?php esc_html_e('Generated Image', 'ai-imagen'); ?></h2>
                    <span class="preview-subtitle"><?php esc_html_e('(appears here after generation)', 'ai-imagen'); ?></span>
                </div>
                <button type="button" class="preview-dock-toggle" id="ai-imagen-preview-dock-toggle" title="<?php esc_attr_e('Expand to fullscreen', 'ai-imagen'); ?>">
                    <span class="dashicons dashicons-editor-expand"></span>
                    <span class="dock-toggle-text"><?php esc_html_e('Expand', 'ai-imagen'); ?></span>
                </button>
            </div>

            <div class="preview-content" id="ai-imagen-preview-area">
                <div class="preview-placeholder">
                    <span class="dashicons dashicons-format-image"></span>
                    <p><?php esc_html_e('Your AI-generated image will appear here', 'ai-imagen'); ?></p>
                    <p class="preview-hint"><?php esc_html_e('Enter a prompt and click "Generate Image" to begin', 'ai-imagen'); ?></p>
                </div>

                <!-- Loading Animation -->
                <div class="preview-loading" id="ai-imagen-preview-loading" style="display: none;">
                    <div class="loading-spinner">
                        <div class="spinner-circle"></div>
                        <div class="spinner-circle"></div>
                        <div class="spinner-circle"></div>
                    </div>
                    <p class="loading-text"><?php esc_html_e('Generating your image...', 'ai-imagen'); ?></p>
                    <p class="loading-subtext"><?php esc_html_e('This may take 10-30 seconds', 'ai-imagen'); ?></p>
                </div>
            </div>

            <div class="preview-actions" id="ai-imagen-preview-actions" style="display: none;">
                <button type="button" class="button" id="ai-imagen-download-btn">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Download', 'ai-imagen'); ?>
                </button>
                <button type="button" class="button button-primary" id="ai-imagen-save-library-btn">
                    <span class="dashicons dashicons-admin-media"></span>
                    <?php esc_html_e('Save to Library', 'ai-imagen'); ?>
                </button>
                <button type="button" class="button" id="ai-imagen-regenerate-btn">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Regenerate', 'ai-imagen'); ?>
                </button>
            </div>

            <!-- Image History Carousel -->
            <div class="preview-history" id="ai-imagen-preview-history" style="display: none;">
                <div class="history-header">
                    <h3><?php esc_html_e('Recent Generations', 'ai-imagen'); ?></h3>
                    <button type="button" class="button button-link history-clear-btn" id="ai-imagen-clear-history">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Clear', 'ai-imagen'); ?>
                    </button>
                </div>
                <div class="history-carousel" id="ai-imagen-history-carousel">
                    <!-- Thumbnails will be added here dynamically -->
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Preview Lightbox Modal -->
<div class="ai-imagen-preview-modal" id="ai-imagen-preview-modal">
    <div class="preview-modal-overlay" id="ai-imagen-preview-modal-overlay"></div>
    <div class="preview-modal-content">
        <div class="preview-modal-header">
            <h2><?php esc_html_e('Generated Image', 'ai-imagen'); ?></h2>
            <button type="button" class="preview-modal-close" id="ai-imagen-preview-modal-close" title="<?php esc_attr_e('Close', 'ai-imagen'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="preview-modal-body" id="ai-imagen-preview-modal-body">
            <!-- Image will be inserted here -->
        </div>
        <div class="preview-modal-actions">
            <button type="button" class="button" id="ai-imagen-modal-download-btn">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download', 'ai-imagen'); ?>
            </button>
            <button type="button" class="button button-primary" id="ai-imagen-modal-save-library-btn">
                <span class="dashicons dashicons-admin-media"></span>
                <?php esc_html_e('Save to Library', 'ai-imagen'); ?>
            </button>
            <button type="button" class="button" id="ai-imagen-modal-regenerate-btn">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Regenerate', 'ai-imagen'); ?>
            </button>
        </div>
    </div>
</div>

