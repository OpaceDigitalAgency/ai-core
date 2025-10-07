# AI-Imagen Installation & Testing Guide

## Installation Steps

### 1. Prerequisites
Ensure you have:
- WordPress 5.8+ installed
- PHP 7.4+ configured
- AI-Core plugin installed and activated
- At least one AI provider configured in AI-Core (OpenAI, Gemini, or xAI Grok)

### 2. Install AI-Imagen

#### Option A: Manual Installation
1. Copy the `ai-imagen` folder to `/wp-content/plugins/`
2. Navigate to WordPress Admin > Plugins
3. Find "AI-Imagen" in the list
4. Click "Activate"

#### Option B: ZIP Installation
1. Zip the `ai-imagen` folder
2. Navigate to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin"
4. Choose the ZIP file
5. Click "Install Now"
6. Click "Activate Plugin"

### 3. Verify Installation

After activation, you should see:
- ✅ "AI-Imagen" menu item in WordPress admin sidebar
- ✅ No error messages
- ✅ Prompt templates installed in AI-Core Prompt Library

## Testing Checklist

### Phase 1: Basic Functionality

#### 1.1 Plugin Activation
- [ ] Plugin activates without errors
- [ ] Admin menu appears with "AI-Imagen" item
- [ ] Submenu items visible: Generate, History, Statistics, Settings

#### 1.2 Dependency Checking
- [ ] Deactivate AI-Core → Should show dependency notice
- [ ] Reactivate AI-Core → Notice disappears
- [ ] No AI providers configured → Should show configuration notice

#### 1.3 Settings Page
- [ ] Navigate to AI-Imagen > Settings
- [ ] All settings fields visible and functional
- [ ] Default values loaded correctly
- [ ] Save settings → Success message appears
- [ ] Settings persist after page reload

### Phase 2: Generator Interface

#### 2.1 Workflow Tabs
- [ ] "Just Start" tab active by default
- [ ] Click "By Use Case" → Panel switches
- [ ] Click "By Role" → Panel switches
- [ ] Click "By Style" → Panel switches
- [ ] Active tab highlighted correctly

#### 2.2 Use Case Selection
- [ ] All 9 use case cards visible
- [ ] Click card → Card highlights as selected
- [ ] Icons and descriptions display correctly
- [ ] Only one card selected at a time

#### 2.3 Role Selection
- [ ] All 8 role cards visible
- [ ] Click card → Card highlights as selected
- [ ] Icons and descriptions display correctly
- [ ] Only one card selected at a time

#### 2.4 Style Selection
- [ ] All 9 style cards visible
- [ ] Click card → Card highlights as selected
- [ ] Icons and descriptions display correctly
- [ ] Only one card selected at a time

#### 2.5 Prompt Input
- [ ] Main prompt textarea functional
- [ ] Additional details textarea functional
- [ ] Quick idea buttons populate prompt field
- [ ] "Enhance with AI" button visible (if enabled in settings)
- [ ] "Load from Library" button visible

#### 2.6 Generation Settings
- [ ] Provider dropdown shows only configured providers
- [ ] Model dropdown loads when provider selected
- [ ] Model dropdown shows only image-capable models
- [ ] Quality dropdown functional (Standard/HD)
- [ ] Aspect ratio dropdown functional (1:1, 4:3, 16:9, 9:16)

### Phase 3: Image Generation

#### 3.1 Basic Generation
- [ ] Enter prompt: "Professional product photo on white background"
- [ ] Click "Generate Image"
- [ ] Button shows "Generating..." state
- [ ] Preview area shows loading indicator
- [ ] Image appears in preview after generation
- [ ] Success message displays
- [ ] Preview actions buttons appear (Download, Save to Library, Regenerate)

#### 3.2 Provider Testing

**OpenAI:**
- [ ] Select provider: OpenAI
- [ ] Select model: dall-e-3
- [ ] Generate image → Success
- [ ] Image URL valid and accessible

**Gemini (if configured):**
- [ ] Select provider: Gemini
- [ ] Model dropdown shows only -image models
- [ ] Generate image → Success
- [ ] Image URL valid and accessible

**Grok (if configured):**
- [ ] Select provider: Grok
- [ ] Model dropdown shows image models
- [ ] Generate image → Success
- [ ] Image URL valid and accessible

#### 3.3 Prompt Enhancement
- [ ] Enable prompt enhancement in settings
- [ ] Enter simple prompt: "cat"
- [ ] Click "Enhance with AI"
- [ ] Prompt enhanced with details
- [ ] Enhanced prompt appears in textarea

#### 3.4 Image Actions
- [ ] Click "Download" → Image downloads
- [ ] Click "Save to Library" → Success message
- [ ] Check Media Library → Image present with metadata
- [ ] Click "Regenerate" → New image generated

### Phase 4: History Page

#### 4.1 History Display
- [ ] Navigate to AI-Imagen > History
- [ ] Generated images display in grid
- [ ] Each item shows: image, provider, model, date, prompt
- [ ] Tags display for use case, role, style (if used)

#### 4.2 History Actions
- [ ] Click "Download" → Image downloads
- [ ] Click "Edit" → Opens media library edit page
- [ ] Click "Delete" → Confirmation prompt
- [ ] Confirm delete → Image removed from grid
- [ ] Check Media Library → Image deleted

### Phase 5: Statistics Page

#### 5.1 Statistics Display
- [ ] Navigate to AI-Imagen > Statistics
- [ ] Summary cards show: Total, Today, This Week, This Month
- [ ] Provider distribution table displays
- [ ] Use case distribution table displays
- [ ] Style distribution table displays
- [ ] Percentages calculate correctly

#### 5.2 Statistics Actions
- [ ] Click "Export as CSV" → CSV file downloads
- [ ] Open CSV → Data formatted correctly
- [ ] Click "Reset Statistics" → Confirmation prompt
- [ ] Confirm reset → Statistics cleared

### Phase 6: Prompt Library Integration

#### 6.1 Template Installation
- [ ] Navigate to AI-Core > Prompt Library
- [ ] AI-Imagen groups present:
  - [ ] Marketing & Advertising
  - [ ] Social Media Content
  - [ ] Product Photography
  - [ ] Website Design Elements
  - [ ] Publishing & Editorial
  - [ ] Presentation Graphics
  - [ ] Game Development
  - [ ] Educational Content
  - [ ] Print-on-Demand

#### 6.2 Template Usage
- [ ] Open any AI-Imagen group
- [ ] Prompts display correctly
- [ ] Click "Run Prompt" → Loads in test interface
- [ ] Prompts marked as type: "image"

### Phase 7: Settings & Limits

#### 7.1 Generation Limits
- [ ] Set daily limit to 2 in settings
- [ ] Generate 2 images successfully
- [ ] Attempt 3rd generation → Limit reached notice
- [ ] Wait until next day OR reset limit
- [ ] Can generate again

#### 7.2 Auto-Save Setting
- [ ] Enable "Auto Save to Media Library"
- [ ] Generate image → Automatically saved
- [ ] Disable "Auto Save to Media Library"
- [ ] Generate image → Not automatically saved
- [ ] Manual save still works

#### 7.3 Feature Toggles
- [ ] Disable "Enable Prompt Enhancement"
- [ ] "Enhance with AI" button hidden
- [ ] Enable again → Button reappears
- [ ] Disable "Enable Scene Builder"
- [ ] Scene builder features hidden (future feature)

### Phase 8: Error Handling

#### 8.1 Validation
- [ ] Empty prompt → Error message
- [ ] No provider selected → Error message
- [ ] Invalid API key → Error message from provider
- [ ] Network error → Appropriate error message

#### 8.2 Edge Cases
- [ ] Very long prompt (1000+ characters) → Handles gracefully
- [ ] Special characters in prompt → Sanitised correctly
- [ ] Rapid clicking generate button → Prevents duplicate requests
- [ ] Browser back button → State preserved

### Phase 9: WordPress.org Compliance

#### 9.1 Security
- [ ] All AJAX requests use nonce verification
- [ ] All admin pages check user capabilities
- [ ] All output properly escaped (esc_html, esc_attr, esc_url)
- [ ] All input properly sanitised
- [ ] No SQL injection vulnerabilities

#### 9.2 Internationalisation
- [ ] All strings wrapped in translation functions
- [ ] Text domain 'ai-imagen' used consistently
- [ ] No hardcoded English strings

#### 9.3 Uninstall
- [ ] Deactivate plugin → No errors
- [ ] Delete plugin → uninstall.php runs
- [ ] Options deleted from database
- [ ] (Optional) Generated images remain in media library

### Phase 10: Performance

#### 10.1 Load Times
- [ ] Generator page loads < 2 seconds
- [ ] History page loads < 2 seconds
- [ ] Statistics page loads < 2 seconds
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug log

#### 10.2 Resource Usage
- [ ] CSS files load correctly
- [ ] JavaScript files load correctly
- [ ] No 404 errors for assets
- [ ] Assets versioned correctly for cache busting

## Common Issues & Solutions

### Issue: "AI-Core Not Configured" Notice
**Solution:** Configure at least one AI provider in AI-Core settings with valid API key.

### Issue: "No Image Generation Providers Available"
**Solution:** Ensure you've configured OpenAI, Gemini, or xAI Grok (not Anthropic, as it doesn't support image generation).

### Issue: Model Dropdown Empty
**Solution:** Check that the selected provider has image-capable models configured.

### Issue: Generation Fails
**Solution:** 
1. Check API key validity
2. Check API credit balance
3. Check prompt for policy violations
4. Check browser console for JavaScript errors
5. Check WordPress debug log for PHP errors

### Issue: Images Not Saving to Media Library
**Solution:**
1. Check WordPress upload directory permissions
2. Check available disk space
3. Check PHP memory limit
4. Enable WordPress debug mode for detailed errors

## Testing Completion

Once all checkboxes are ticked:
- ✅ Plugin is ready for production use
- ✅ All features working as expected
- ✅ No critical bugs found
- ✅ WordPress.org compliance verified

## Next Steps

1. Create plugin ZIP for distribution
2. Submit to WordPress.org (if applicable)
3. Create user documentation
4. Set up support channels
5. Monitor for user feedback

## Support

For issues during testing:
1. Check WordPress debug log: `wp-content/debug.log`
2. Check browser console for JavaScript errors
3. Check Network tab for failed AJAX requests
4. Enable AI-Core debug mode for API request details
5. Contact Opace Digital Agency for assistance

