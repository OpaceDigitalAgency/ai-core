<?php
/**
 * AI-Core Prompt Library Class
 *
 * Manages prompt library with groups, search, filter, import/export
 *
 * @package AI_Core
 * @version 0.5.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load AJAX trait
require_once AI_CORE_PLUGIN_DIR . 'admin/class-ai-core-prompt-library-ajax.php';

/**
 * AI-Core Prompt Library Class
 *
 * Manages prompt catalogue with modern UX
 */
class AI_Core_Prompt_Library {

    use AI_Core_Prompt_Library_AJAX;
    
    /**
     * Class instance
     * 
     * @var AI_Core_Prompt_Library
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Prompt_Library
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
        try {
            $this->init();
        } catch (Exception $e) {
            error_log('AI-Core Prompt Library: Initialisation error - ' . $e->getMessage());
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    private function init() {
        // AJAX handlers
        add_action('wp_ajax_ai_core_get_prompts', array($this, 'ajax_get_prompts'));
        add_action('wp_ajax_ai_core_save_prompt', array($this, 'ajax_save_prompt'));
        add_action('wp_ajax_ai_core_delete_prompt', array($this, 'ajax_delete_prompt'));
        add_action('wp_ajax_ai_core_get_groups', array($this, 'ajax_get_groups'));
        add_action('wp_ajax_ai_core_save_group', array($this, 'ajax_save_group'));
        add_action('wp_ajax_ai_core_delete_group', array($this, 'ajax_delete_group'));
        add_action('wp_ajax_ai_core_move_prompt', array($this, 'ajax_move_prompt'));
        add_action('wp_ajax_ai_core_run_prompt', array($this, 'ajax_run_prompt'));
        add_action('wp_ajax_ai_core_export_prompts', array($this, 'ajax_export_prompts'));
        add_action('wp_ajax_ai_core_import_prompts', array($this, 'ajax_import_prompts'));
        add_action('wp_ajax_ai_core_get_provider_capabilities', array($this, 'ajax_get_provider_capabilities'));
    }
    
    /**
     * Render prompt library page
     *
     * @return void
     */
    public function render_page() {
        // Add error handling and debugging
        try {
            // Increase timeout for large datasets
            set_time_limit(60);

            $groups = $this->get_groups();
            $prompts = $this->get_prompts();

            // Debug logging
            error_log('AI-Core Prompt Library: Loaded ' . count($groups) . ' groups and ' . count($prompts) . ' prompts');
        } catch (Exception $e) {
            error_log('AI-Core Prompt Library Error: ' . $e->getMessage());
            echo '<div class="wrap"><h1>Prompt Library</h1>';
            echo '<div class="notice notice-error"><p>Error loading Prompt Library: ' . esc_html($e->getMessage()) . '</p></div>';
            echo '</div>';
            return;
        }

        ?>
        <div class="wrap ai-core-prompt-library">
            <h1><?php esc_html_e('Prompt Library', 'ai-core'); ?></h1>
            
            <div class="ai-core-library-header">
                <div class="ai-core-library-actions">
                    <button type="button" class="button button-primary" id="ai-core-new-prompt">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('New Prompt', 'ai-core'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="ai-core-new-group">
                        <span class="dashicons dashicons-category"></span>
                        <?php esc_html_e('New Group', 'ai-core'); ?>
                    </button>
                    <button type="button" class="button" id="ai-core-import-prompts">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Import', 'ai-core'); ?>
                    </button>
                    <button type="button" class="button" id="ai-core-export-prompts">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export', 'ai-core'); ?>
                    </button>
                    <a href="<?php echo esc_url( AI_CORE_PLUGIN_URL . 'prompts-template.json' ); ?>"
                       class="button"
                       download
                       title="<?php esc_attr_e('Download JSON template file', 'ai-core'); ?>">
                        <span class="dashicons dashicons-media-code"></span>
                        <?php esc_html_e('JSON Template', 'ai-core'); ?>
                    </a>
                    <a href="<?php echo esc_url( AI_CORE_PLUGIN_URL . 'prompts-template.csv' ); ?>"
                       class="button"
                       download
                       title="<?php esc_attr_e('Download CSV template file', 'ai-core'); ?>">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <?php esc_html_e('CSV Template', 'ai-core'); ?>
                    </a>
                </div>

                <div class="ai-core-library-search">
                    <input type="search"
                           id="ai-core-search-prompts"
                           class="regular-text"
                           placeholder="<?php esc_attr_e('Search prompts...', 'ai-core'); ?>" />
                    <select id="ai-core-filter-group" class="regular-text">
                        <option value=""><?php esc_html_e('All Groups', 'ai-core'); ?></option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr($group['id']); ?>">
                                <?php echo esc_html($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="ai-core-library-content">
                <!-- Card-based group layout -->
                <div id="ai-core-groups-container" class="ai-core-groups-container">
                    <?php if (empty($groups)): ?>
                        <div class="ai-core-empty-state">
                            <span class="dashicons dashicons-category"></span>
                            <h3><?php esc_html_e('No groups yet', 'ai-core'); ?></h3>
                            <p><?php esc_html_e('Create your first group to organise prompts.', 'ai-core'); ?></p>
                            <button type="button" class="button button-primary" id="ai-core-new-group-empty">
                                <?php esc_html_e('Create Group', 'ai-core'); ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <?php
                        // Organise prompts by group
                        $prompts_by_group = array();
                        foreach ($prompts as $prompt) {
                            $group_id = $prompt['group_id'] ?? 0;
                            if (!isset($prompts_by_group[$group_id])) {
                                $prompts_by_group[$group_id] = array();
                            }
                            $prompts_by_group[$group_id][] = $prompt;
                        }

                        // Render each group as a card
                        foreach ($groups as $group):
                            $group_prompts = $prompts_by_group[$group['id']] ?? array();
                        ?>
                            <div class="ai-core-group-card" data-group-id="<?php echo esc_attr($group['id']); ?>">
                                <div class="group-card-header">
                                    <div class="group-card-title">
                                        <span class="dashicons dashicons-category"></span>
                                        <h3><?php echo esc_html($group['name']); ?></h3>
                                        <span class="group-count"><?php echo count($group_prompts); ?></span>
                                    </div>
                                    <div class="group-card-actions">
                                        <button type="button" class="button-link edit-group" title="<?php esc_attr_e('Edit Group', 'ai-core'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="button-link delete-group" title="<?php esc_attr_e('Delete Group', 'ai-core'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                        <button type="button" class="button-link add-prompt-to-group" title="<?php esc_attr_e('Add Prompt', 'ai-core'); ?>">
                                            <span class="dashicons dashicons-plus-alt"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="group-card-body" data-group-id="<?php echo esc_attr($group['id']); ?>">
                                    <?php if (empty($group_prompts)): ?>
                                        <div class="group-empty-state">
                                            <span class="dashicons dashicons-admin-post"></span>
                                            <p><?php esc_html_e('No prompts in this group', 'ai-core'); ?></p>
                                            <p class="description"><?php esc_html_e('Drag prompts here or click + to add', 'ai-core'); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($group_prompts as $prompt): ?>
                                            <?php $this->render_prompt_card($prompt); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Ungrouped prompts -->
                        <?php
                        $ungrouped_prompts = $prompts_by_group[0] ?? array();
                        if (!empty($ungrouped_prompts)):
                        ?>
                            <div class="ai-core-group-card ungrouped" data-group-id="0">
                                <div class="group-card-header">
                                    <div class="group-card-title">
                                        <span class="dashicons dashicons-admin-post"></span>
                                        <h3><?php esc_html_e('Ungrouped Prompts', 'ai-core'); ?></h3>
                                        <span class="group-count"><?php echo count($ungrouped_prompts); ?></span>
                                    </div>
                                </div>
                                <div class="group-card-body" data-group-id="0">
                                    <?php foreach ($ungrouped_prompts as $prompt): ?>
                                        <?php $this->render_prompt_card($prompt); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Prompt Editor Modal -->
        <div id="ai-core-prompt-modal" class="ai-core-modal" style="display: none;">
            <div class="ai-core-modal-content">
                <div class="ai-core-modal-header">
                    <h2 id="ai-core-modal-title"><?php esc_html_e('Edit Prompt', 'ai-core'); ?></h2>
                    <button type="button" class="ai-core-modal-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <div class="ai-core-modal-body">
                    <input type="hidden" id="prompt-id" value="" />
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="prompt-title"><?php esc_html_e('Title', 'ai-core'); ?></label></th>
                            <td><input type="text" id="prompt-title" class="large-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="prompt-group"><?php esc_html_e('Group', 'ai-core'); ?></label></th>
                            <td>
                                <select id="prompt-group" class="regular-text">
                                    <option value=""><?php esc_html_e('Ungrouped', 'ai-core'); ?></option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo esc_attr($group['id']); ?>">
                                            <?php echo esc_html($group['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="prompt-content"><?php esc_html_e('Prompt', 'ai-core'); ?></label></th>
                            <td><textarea id="prompt-content" rows="8" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="prompt-provider"><?php esc_html_e('Provider', 'ai-core'); ?></label></th>
                            <td>
                                <select id="prompt-provider" class="regular-text">
                                    <option value=""><?php esc_html_e('Default', 'ai-core'); ?></option>
                                    <option value="openai">OpenAI</option>
                                    <option value="anthropic">Anthropic Claude</option>
                                    <option value="gemini">Google Gemini</option>
                                    <option value="grok">xAI Grok</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="prompt-type"><?php esc_html_e('Type', 'ai-core'); ?></label></th>
                            <td>
                                <select id="prompt-type" class="regular-text">
                                    <option value="text"><?php esc_html_e('Text Generation', 'ai-core'); ?></option>
                                    <option value="image"><?php esc_html_e('Image Generation', 'ai-core'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="ai-core-prompt-test">
                        <h3><?php esc_html_e('Test Prompt', 'ai-core'); ?></h3>
                        <button type="button" class="button" id="ai-core-test-prompt-modal">
                            <span class="dashicons dashicons-controls-play"></span>
                            <?php esc_html_e('Run Prompt', 'ai-core'); ?>
                        </button>
                        <div id="ai-core-prompt-result" class="ai-core-prompt-result" style="display: none;"></div>
                    </div>
                </div>
                <div class="ai-core-modal-footer">
                    <button type="button" class="button button-primary" id="ai-core-save-prompt">
                        <?php esc_html_e('Save Prompt', 'ai-core'); ?>
                    </button>
                    <button type="button" class="button" id="ai-core-cancel-prompt">
                        <?php esc_html_e('Cancel', 'ai-core'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Group Editor Modal -->
        <div id="ai-core-group-modal" class="ai-core-modal" style="display: none;">
            <div class="ai-core-modal-content ai-core-modal-small">
                <div class="ai-core-modal-header">
                    <h2 id="ai-core-group-modal-title"><?php esc_html_e('Edit Group', 'ai-core'); ?></h2>
                    <button type="button" class="ai-core-modal-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <div class="ai-core-modal-body">
                    <input type="hidden" id="group-id" value="" />
                    <table class="form-table">
                        <tr>
                            <th><label for="group-name"><?php esc_html_e('Group Name', 'ai-core'); ?></label></th>
                            <td><input type="text" id="group-name" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="group-description"><?php esc_html_e('Description', 'ai-core'); ?></label></th>
                            <td><textarea id="group-description" rows="3" class="large-text"></textarea></td>
                        </tr>
                    </table>
                </div>
                <div class="ai-core-modal-footer">
                    <button type="button" class="button button-primary" id="ai-core-save-group">
                        <?php esc_html_e('Save Group', 'ai-core'); ?>
                    </button>
                    <button type="button" class="button" id="ai-core-cancel-group">
                        <?php esc_html_e('Cancel', 'ai-core'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Import Modal -->
        <div id="ai-core-import-modal" class="ai-core-modal" style="display: none;">
            <div class="ai-core-modal-content ai-core-modal-small">
                <div class="ai-core-modal-header">
                    <h2><?php esc_html_e('Import Prompts', 'ai-core'); ?></h2>
                    <button type="button" class="ai-core-modal-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <div class="ai-core-modal-body">
                    <p><?php esc_html_e('Upload a JSON file containing prompts and groups.', 'ai-core'); ?></p>
                    <input type="file" id="ai-core-import-file" accept=".json" />

                    <div class="ai-core-import-templates" style="margin-top: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;">
                            <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                            <?php esc_html_e('Need a template?', 'ai-core'); ?>
                        </h4>
                        <p style="margin-bottom: 10px; color: #50575e; font-size: 13px;">
                            <?php esc_html_e('Download a template file to see the correct format for importing prompts:', 'ai-core'); ?>
                        </p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="<?php echo esc_url( AI_CORE_PLUGIN_URL . 'prompts-template.json' ); ?>"
                               class="button"
                               download
                               style="display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-media-code" style="font-size: 16px;"></span>
                                <?php esc_html_e('Download JSON Template', 'ai-core'); ?>
                            </a>
                            <a href="<?php echo esc_url( AI_CORE_PLUGIN_URL . 'prompts-template.csv' ); ?>"
                               class="button"
                               download
                               style="display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-media-spreadsheet" style="font-size: 16px;"></span>
                                <?php esc_html_e('Download CSV Template', 'ai-core'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="ai-core-modal-footer">
                    <button type="button" class="button button-primary" id="ai-core-do-import">
                        <?php esc_html_e('Import', 'ai-core'); ?>
                    </button>
                    <button type="button" class="button" id="ai-core-cancel-import">
                        <?php esc_html_e('Cancel', 'ai-core'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <div id="ai-core-export-modal" class="ai-core-modal" style="display: none;">
            <div class="ai-core-modal-content ai-core-modal-small">
                <div class="ai-core-modal-header">
                    <h2><?php esc_html_e('Export Prompts', 'ai-core'); ?></h2>
                    <button type="button" class="ai-core-modal-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <div class="ai-core-modal-body">
                    <p><?php esc_html_e('Choose export format and version.', 'ai-core'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ai-core-export-format"><?php esc_html_e('Format', 'ai-core'); ?></label>
                            </th>
                            <td>
                                <select id="ai-core-export-format" class="regular-text">
                                    <option value="json"><?php esc_html_e('JSON', 'ai-core'); ?></option>
                                    <option value="csv"><?php esc_html_e('CSV', 'ai-core'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="ai-core-export-version"><?php esc_html_e('Version', 'ai-core'); ?></label>
                            </th>
                            <td>
                                <select id="ai-core-export-version" class="regular-text">
                                    <option value="1.0">1.0</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="ai-core-modal-footer">
                    <button type="button" class="button button-primary" id="ai-core-do-export">
                        <?php esc_html_e('Export', 'ai-core'); ?>
                    </button>
                    <button type="button" class="button" id="ai-core-cancel-export">
                        <?php esc_html_e('Cancel', 'ai-core'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render prompt card
     *
     * @param array $prompt Prompt data
     * @return void
     */
    private function render_prompt_card($prompt) {
        $prompt_id = esc_attr($prompt['id']);
        $title = esc_html($prompt['title']);
        $content = esc_html(wp_trim_words($prompt['content'], 20));
        $group_id = esc_attr($prompt['group_id'] ?? '');
        $type = esc_html($prompt['type'] ?? 'text');
        $provider = esc_html($prompt['provider'] ?? 'default');

        ?>
        <div class="ai-core-prompt-card" data-prompt-id="<?php echo $prompt_id; ?>" data-group-id="<?php echo $group_id; ?>">
            <div class="prompt-card-header">
                <h4><?php echo $title; ?></h4>
                <div class="prompt-card-actions">
                    <button type="button" class="button-link edit-prompt" data-prompt-id="<?php echo $prompt_id; ?>" title="<?php esc_attr_e('Edit', 'ai-core'); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="button-link delete-prompt" data-prompt-id="<?php echo $prompt_id; ?>" title="<?php esc_attr_e('Delete', 'ai-core'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <div class="prompt-card-body">
                <p><?php echo $content; ?></p>
            </div>
            <div class="prompt-card-footer">
                <span class="prompt-type">
                    <span class="dashicons dashicons-<?php echo $type === 'image' ? 'format-image' : 'text'; ?>"></span>
                    <?php echo ucfirst($type); ?>
                </span>
                <span class="prompt-provider"><?php echo ucfirst($provider); ?></span>
                <button type="button" class="button button-small run-prompt" data-prompt-id="<?php echo $prompt_id; ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php esc_html_e('Run', 'ai-core'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Get all groups
     *
     * @return array
     */
    public function get_groups() {
        global $wpdb;
        $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
        $prompts_table = $wpdb->prefix . 'ai_core_prompts';

        // Check if tables exist
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$groups_table}'");
        if (!$table_exists) {
            error_log('AI-Core: Prompt groups table does not exist');
            return array();
        }

        // Optimised query: Get groups with prompt counts in a single query
        $groups = $wpdb->get_results(
            "SELECT g.*, COUNT(p.id) as count
             FROM {$groups_table} g
             LEFT JOIN {$prompts_table} p ON g.id = p.group_id
             GROUP BY g.id
             ORDER BY g.name ASC",
            ARRAY_A
        );

        if ($wpdb->last_error) {
            error_log('AI-Core: Database error in get_groups(): ' . $wpdb->last_error);
            return array();
        }

        return $groups ?: array();
    }

    /**
     * Get prompt count for a group
     *
     * @param int $group_id Group ID
     * @return int
     */
    private function get_group_prompt_count($group_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_core_prompts';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE group_id = %d",
            $group_id
        ));
    }

    /**
     * Get all prompts
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_prompts($args = array()) {
        global $wpdb;
        $prompts_table = $wpdb->prefix . 'ai_core_prompts';
        $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';

        // Check if tables exist
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prompts_table}'");
        if (!$table_exists) {
            error_log('AI-Core: Prompts table does not exist');
            return array();
        }

        $defaults = array(
            'group_id' => null,
            'search' => '',
            'type' => '',
            'provider' => '',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $prepare_args = array();

        if (!is_null($args['group_id'])) {
            $where[] = 'p.group_id = %d';
            $prepare_args[] = $args['group_id'];
        }

        if (!empty($args['search'])) {
            $where[] = '(p.title LIKE %s OR p.content LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $prepare_args[] = $search_term;
            $prepare_args[] = $search_term;
        }

        if (!empty($args['type'])) {
            $where[] = 'p.type = %s';
            $prepare_args[] = $args['type'];
        }

        if (!empty($args['provider'])) {
            $where[] = 'p.provider = %s';
            $prepare_args[] = $args['provider'];
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT p.*, g.name as group_name
                  FROM {$prompts_table} p
                  LEFT JOIN {$groups_table} g ON p.group_id = g.id
                  WHERE {$where_clause}
                  ORDER BY p.created_at DESC";

        if (!empty($prepare_args)) {
            $query = $wpdb->prepare($query, $prepare_args);
        }

        $prompts = $wpdb->get_results($query, ARRAY_A);

        if ($wpdb->last_error) {
            error_log('AI-Core: Database error in get_prompts(): ' . $wpdb->last_error);
            return array();
        }

        return $prompts ?: array();
    }

    /**
     * AJAX: Get prompts
     *
     * @return void
     */
    public function ajax_get_prompts() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $args = array(
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '',
            'provider' => isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '',
        );

        // Only add group_id filter if explicitly set (not "All Prompts")
        if (isset($_POST['group_id']) && $_POST['group_id'] !== '' && $_POST['group_id'] !== 'null') {
            $args['group_id'] = intval($_POST['group_id']);
        }

        $prompts = $this->get_prompts($args);

        wp_send_json_success(array('prompts' => $prompts));
    }
}

// Initialize Prompt Library to register AJAX handlers
AI_Core_Prompt_Library::get_instance();
