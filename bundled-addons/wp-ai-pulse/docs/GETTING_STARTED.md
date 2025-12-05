# Getting Started with AI-Pulse Implementation

**Approach:** Pure PHP WordPress Plugin (Approach 1)  
**Estimated Time:** 23-27 hours  
**Prerequisites:** AI-Core plugin installed and configured

---

## Phase 1: Core Plugin Structure (3-4 hours)

### Step 1.1: Create Main Plugin File

**File:** `bundled-addons/ai-pulse/ai-pulse.php`

**Tasks:**
- [ ] Add plugin header with all required fields
- [ ] Define plugin constants (VERSION, PLUGIN_DIR, PLUGIN_URL, etc.)
- [ ] Implement singleton pattern (AI_Pulse class)
- [ ] Add dependency checking (check for ai_core())
- [ ] Add activation/deactivation hooks
- [ ] Create global `ai_pulse()` helper function

**Reference:** Lines 370-589 in AI_PULSE_IMPLEMENTATION_PLAN.md

---

### Step 1.2: Create Database Schema

**File:** `bundled-addons/ai-pulse/includes/class-ai-pulse-database.php`

**Tasks:**
- [ ] Create `wp_ai_pulse_content` table
- [ ] Create `wp_ai_pulse_settings` table
- [ ] Add indexes for performance
- [ ] Implement `create_tables()` method
- [ ] Implement `store_content()` method
- [ ] Implement `get_active_content()` method

**Reference:** Lines 225-245 in AI_PULSE_IMPLEMENTATION_PLAN.md

---

### Step 1.3: Create Settings Class

**File:** `bundled-addons/ai-pulse/includes/class-ai-pulse-settings.php`

**Tasks:**
- [ ] Define default settings array
- [ ] Implement `get_all()` method
- [ ] Implement `get($key)` method
- [ ] Implement `set($key, $value)` method
- [ ] Implement `set_defaults()` method (for activation)

**Default Settings:**
```php
array(
    'update_interval' => 'daily',
    'start_time' => '03:00',
    'gradual_rollout_enabled' => true,
    'rollout_window_hours' => 2,
    'delay_between_requests' => 2,
    'max_concurrent_generations' => 3,
    'default_period' => 'weekly',
    'default_location' => 'Birmingham, West Midlands, UK',
    'keywords' => array(),
    'pause_on_error' => true,
    'max_errors' => 3,
    'email_notifications' => true
)
```

---

### Step 1.4: Create Admin Menu Structure

**File:** `bundled-addons/ai-pulse/admin/class-ai-pulse-admin.php`

**Tasks:**
- [ ] Add submenu under AI-Core menu
- [ ] Create tabbed interface (6 tabs)
- [ ] Enqueue admin CSS/JS with cache busting
- [ ] Add admin notices for errors/success

**Menu Structure:**
```php
add_submenu_page(
    'ai-core',  // Parent menu (AI-Core)
    __('AI-Pulse', 'ai-pulse'),
    __('AI-Pulse', 'ai-pulse'),
    'manage_options',
    'ai-pulse',
    array($this, 'render_settings_page')
);
```

---

### Step 1.5: Create Basic CSS Framework

**File:** `bundled-addons/ai-pulse/assets/css/admin.css`

**Tasks:**
- [ ] Define CSS variables (TrendPulse colour palette)
- [ ] Create card-based layout styles
- [ ] Create button styles (primary, secondary, group)
- [ ] Create input field styles
- [ ] Create tab navigation styles

**Reference:** Lines 1165-1700 in AI_PULSE_IMPLEMENTATION_PLAN.md

---

## Phase 2: Test Interface (3-4 hours)

### Step 2.1: Create Test Interface Tab

**File:** `bundled-addons/ai-pulse/admin/views/tab-test-interface.php`

**Tasks:**
- [ ] Create keyword input field
- [ ] Create period selector (button group)
- [ ] Create mode dropdown
- [ ] Create "Go" button
- [ ] Add cost estimation display
- [ ] Add prompt preview section
- [ ] Add results display area

**Reference:** Lines 493-542 in AI_PULSE_IMPLEMENTATION_PLAN.md

---

### Step 2.2: Create AJAX Handler

**File:** `bundled-addons/ai-pulse/admin/class-ai-pulse-ajax.php`

**Tasks:**
- [ ] Register AJAX action `ai_pulse_test_generate`
- [ ] Verify nonce
- [ ] Sanitize inputs
- [ ] Call generator class
- [ ] Return JSON response

**AJAX Response Format:**
```php
array(
    'success' => true,
    'data' => array(
        'html' => '<div>...</div>',
        'json' => array(...),
        'sources' => array(...),
        'tokens' => array(
            'input' => 1234,
            'output' => 5678,
            'total' => 6912
        ),
        'cost' => 0.0167
    )
)
```

---

## Phase 3: Content Generation (4-5 hours)

### Step 3.1: Create Generator Class

**File:** `bundled-addons/ai-pulse/includes/class-ai-pulse-generator.php`

**Tasks:**
- [ ] Implement `generate_content($keyword, $mode, $period)` method
- [ ] Build system instruction with variables
- [ ] Build user prompt for each mode
- [ ] Call AI-Core API (`ai_core()->send_text_request()`)
- [ ] Parse and validate JSON response
- [ ] Convert JSON to HTML
- [ ] Return result array

**Reference:** Lines 705-919 in AI_PULSE_IMPLEMENTATION_PLAN.md

---

### Step 3.2: Create Mode Definitions

**File:** `bundled-addons/ai-pulse/includes/class-ai-pulse-modes.php`

**Tasks:**
- [ ] Define all 11 mode prompts
- [ ] Define JSON structure for each mode
- [ ] Implement `get_prompt($mode)` method
- [ ] Implement `get_structure($mode)` method

**Reference:** Lines 457-473 in AI_PULSE_IMPLEMENTATION_PLAN.md

---

### Step 3.3: Create Validator Class

**File:** `bundled-addons/ai-pulse/includes/class-ai-pulse-validator.php`

**Tasks:**
- [ ] Implement `validate_mode_structure($data, $mode)` method
- [ ] Check required fields for each mode
- [ ] Validate data types
- [ ] Return true/false

---

## Quick Start Checklist

Before you begin:
- [ ] Read `AI_PULSE_IMPLEMENTATION_PLAN.md` (full specification)
- [ ] Read `IMPLEMENTATION_APPROACH_COMPARISON.md` (understand why Pure PHP)
- [ ] Read `ENHANCEMENTS_SUMMARY.md` (see what was added)
- [ ] Ensure AI-Core plugin is installed and active
- [ ] Ensure Gemini API key is configured in AI-Core
- [ ] Create `bundled-addons/ai-pulse/` directory structure

---

## Development Order

1. **Phase 1** - Core structure (can test activation/deactivation)
2. **Phase 2** - Test interface (can test API calls manually)
3. **Phase 3** - Content generation (can generate and store content)
4. **Phase 4** - Shortcode (can display content on frontend)
5. **Phase 5** - Scheduling (can automate generation)
6. **Phase 6** - Admin interface (can manage keywords/settings)
7. **Phase 7** - Testing & polish (can deploy to production)

---

## Testing Strategy

After each phase:
1. Test in local development environment
2. Check for PHP errors/warnings
3. Verify database tables created correctly
4. Test with 1-2 keywords
5. Check browser console for JS errors
6. Verify cache busting (check CSS/JS URLs)

---

## Ready to Start?

**Next Command:**
```bash
cd bundled-addons/ai-pulse
touch ai-pulse.php
```

Then copy the plugin header and singleton pattern from `AI_PULSE_IMPLEMENTATION_PLAN.md` lines 370-589.

**Good luck! 🚀**

