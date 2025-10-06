<?php
/**
 * AI-Imagen History Page
 * 
 * Generated images history interface
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$media = AI_Imagen_Media::get_instance();
?>

<div class="wrap ai-imagen-history">
    <h1><?php esc_html_e('Image Generation History', 'ai-imagen'); ?></h1>
    
    <?php if (empty($images)): ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No generated images yet. Start creating images from the generator page!', 'ai-imagen'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-imagen')); ?>" class="button button-primary">
                    <?php esc_html_e('Generate Images', 'ai-imagen'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>
        <div class="ai-imagen-history-grid">
            <?php foreach ($images as $image): ?>
                <?php
                $image_url = wp_get_attachment_url($image->ID);
                $metadata = $media->get_image_metadata($image->ID);
                $timestamp = isset($metadata['timestamp']) ? $metadata['timestamp'] : get_post_time('U', false, $image->ID);
                ?>
                <div class="history-item" data-id="<?php echo esc_attr($image->ID); ?>">
                    <div class="history-item-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image->post_title); ?>">
                    </div>
                    <div class="history-item-details">
                        <div class="history-item-meta">
                            <span class="meta-provider">
                                <strong><?php esc_html_e('Provider:', 'ai-imagen'); ?></strong>
                                <?php echo esc_html(ucfirst($metadata['provider'])); ?>
                            </span>
                            <span class="meta-model">
                                <strong><?php esc_html_e('Model:', 'ai-imagen'); ?></strong>
                                <?php echo esc_html($metadata['model']); ?>
                            </span>
                            <span class="meta-date">
                                <strong><?php esc_html_e('Date:', 'ai-imagen'); ?></strong>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp)); ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($metadata['prompt'])): ?>
                            <div class="history-item-prompt">
                                <strong><?php esc_html_e('Prompt:', 'ai-imagen'); ?></strong>
                                <p><?php echo esc_html(wp_trim_words($metadata['prompt'], 20)); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="history-item-tags">
                            <?php if (!empty($metadata['use_case'])): ?>
                                <span class="tag tag-use-case"><?php echo esc_html($metadata['use_case']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($metadata['role'])): ?>
                                <span class="tag tag-role"><?php echo esc_html($metadata['role']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($metadata['style'])): ?>
                                <span class="tag tag-style"><?php echo esc_html($metadata['style']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="history-item-actions">
                            <a href="<?php echo esc_url($image_url); ?>" class="button" download>
                                <span class="dashicons dashicons-download"></span>
                                <?php esc_html_e('Download', 'ai-imagen'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $image->ID . '&action=edit')); ?>" class="button">
                                <span class="dashicons dashicons-edit"></span>
                                <?php esc_html_e('Edit', 'ai-imagen'); ?>
                            </a>
                            <button type="button" class="button button-link-delete history-delete-btn" data-id="<?php echo esc_attr($image->ID); ?>">
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Delete', 'ai-imagen'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

