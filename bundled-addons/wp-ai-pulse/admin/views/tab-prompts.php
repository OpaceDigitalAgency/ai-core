<?php
/**
 * Prompt Templates Tab
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$modes = AI_Pulse_Modes::get_all_modes();
$selected_mode = isset($_GET['mode']) ? sanitize_text_field($_GET['mode']) : 'SUMMARY';
$system_instruction = AI_Pulse_Modes::get_system_instruction();
$mode_prompt = AI_Pulse_Modes::get_prompt($selected_mode);
$mode_structure = AI_Pulse_Modes::get_structure($selected_mode);
?>

<div class="ai-pulse-prompts-tab">
    <div class="ai-pulse-card">
        <h2>Prompt Templates</h2>
        <p>Customise AI prompts for each analysis mode. All prompts support variable placeholders.</p>

        <div class="form-row">
            <div class="form-group">
                <label for="mode-selector">Select Mode</label>
                <select id="mode-selector" name="mode">
                    <?php foreach ($modes as $mode_id => $mode_data): ?>
                        <option value="<?php echo esc_attr($mode_id); ?>" <?php selected($selected_mode, $mode_id); ?>>
                            <?php echo esc_html($mode_data['icon'] . ' ' . $mode_data['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php echo esc_html($modes[$selected_mode]['description']); ?></p>
            </div>
        </div>
    </div>

    <div class="ai-pulse-card">
        <h3>System Instruction (Global)</h3>
        <p>This instruction applies to all modes and sets the overall context and rules.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('ai_pulse_settings'); ?>
            <input type="hidden" name="ai_pulse_action" value="save_system_instruction">
            
            <div class="form-group">
                <textarea name="system_instruction" rows="12" class="large-text code"><?php echo esc_textarea($system_instruction); ?></textarea>
            </div>

            <div class="ai-pulse-variables">
                <strong>Available Variables:</strong>
                <code>{current_date}</code>
                <code>{date_range}</code>
                <code>{period_description}</code>
                <code>{keyword}</code>
                <code>{location}</code>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Save System Instruction</button>
                <button type="button" class="button" id="reset-system-instruction">Reset to Default</button>
            </div>
        </form>
    </div>

    <div class="ai-pulse-card">
        <h3>Mode-Specific Prompt: <?php echo esc_html($modes[$selected_mode]['name']); ?></h3>
        
        <form method="post" action="">
            <?php wp_nonce_field('ai_pulse_settings'); ?>
            <input type="hidden" name="ai_pulse_action" value="save_mode_prompt">
            <input type="hidden" name="mode" value="<?php echo esc_attr($selected_mode); ?>">
            
            <div class="form-group">
                <label>Prompt Template</label>
                <textarea name="mode_prompt" rows="15" class="large-text code"><?php echo esc_textarea($mode_prompt); ?></textarea>
            </div>

            <div class="form-group">
                <label>Expected JSON Structure</label>
                <div class="ai-pulse-json-structure">
                    <pre><?php 
                    $example_structure = array();
                    foreach ($mode_structure as $field) {
                        $example_structure[$field] = '...';
                    }
                    echo json_encode($example_structure, JSON_PRETTY_PRINT); 
                    ?></pre>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Save Prompt Template</button>
                <button type="button" class="button" id="reset-mode-prompt">Reset to Default</button>
                <button type="button" class="button" id="test-prompt">Test This Prompt</button>
            </div>
        </form>
    </div>
</div>

<style>
.ai-pulse-prompts-tab .ai-pulse-card {
    margin-bottom: 20px;
}

.ai-pulse-variables {
    margin: 15px 0;
    padding: 10px;
    background: #f8f9fa;
    border-left: 3px solid #2563eb;
}

.ai-pulse-variables code {
    display: inline-block;
    margin: 0 5px;
    padding: 2px 6px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.ai-pulse-json-structure {
    background: #1e293b;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
}

.ai-pulse-json-structure pre {
    margin: 0;
    color: #e2e8f0;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 13px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Mode selector change
    $('#mode-selector').on('change', function() {
        const mode = $(this).val();
        window.location.href = '<?php echo admin_url('admin.php?page=ai-pulse&tab=prompts'); ?>&mode=' + mode;
    });

    // Test prompt button
    $('#test-prompt').on('click', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=ai-pulse&tab=test'); ?>&mode=' + $('#mode-selector').val();
    });
});
</script>

