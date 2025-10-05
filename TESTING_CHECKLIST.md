# Testing Checklist - Version 0.1.0

**Date:** 2025-10-05  
**Focus:** API Compatibility Fixes

---

## Critical Test Cases (From Screenshots)

### Test Setup
1. Navigate to: `https://adwordsadvantage.com/wp-admin/admin.php?page=ai-core-settings`
2. Ensure API keys are configured for all providers
3. Use test prompt: "say hello"

### Test Case 1: Anthropic Claude Sonnet 4.5 ✅
**Before:** `Error: Anthropic API request failed: HTTP 400: max_tokens: Field required`

**Test Steps:**
1. Select Provider: `Anthropic Claude`
2. Select Model: `Claude Sonnet 4.5 (claude-sonnet-4-5-20250929)`
3. Type: `Text Generation`
4. Prompt: `say hello`
5. Click "Run Prompt"

**Expected Result:**
- ✅ Success message
- ✅ Response text displayed (e.g., "Hello! How can I assist you today?")
- ✅ No error about `max_tokens`

**What Was Fixed:**
- Changed ModelRegistry to use `max_tokens` (not `max_output_tokens`)
- Added fallback in AnthropicProvider to ensure `max_tokens` is always present

---

### Test Case 2: OpenAI gpt-4.1 ✅
**Before:** `Error: OpenAI responses API request failed: Invalid OpenAI response: missing choices array`

**Test Steps:**
1. Select Provider: `OpenAI`
2. Select Model: `GPT-4.1 (gpt-4.1)`
3. Type: `Text Generation`
4. Prompt: `say hello`
5. Click "Run Prompt"

**Expected Result:**
- ✅ Success message
- ✅ Response text displayed
- ✅ No error about `missing choices array`

**What Was Fixed:**
- Enhanced ResponseNormalizer to detect and parse Responses API format
- Added `normalizeResponsesAPIResponse()` method to extract content from `output` structure

---

### Test Case 3: OpenAI o3 ✅
**Before:** `Error: OpenAI responses API request failed: Invalid OpenAI response: missing choices array`

**Test Steps:**
1. Select Provider: `OpenAI`
2. Select Model: `OpenAI o3 (o3)`
3. Type: `Text Generation`
4. Prompt: `say hello`
5. Click "Run Prompt"

**Expected Result:**
- ✅ Success message
- ✅ Response text displayed
- ✅ No error about `missing choices array`

**What Was Fixed:**
- Same as Test Case 2 (Responses API parser)

---

### Test Case 4: OpenAI gpt-5 ✅
**Before:** `Error: OpenAI responses API request failed: HTTP 400: Unsupported parameter: 'max_completion_tokens'. In the Responses API, this parameter has moved to 'max_output_tokens'.`

**Test Steps:**
1. Select Provider: `OpenAI`
2. Select Model: `GPT-5 (gpt-5)`
3. Type: `Text Generation`
4. Prompt: `say hello`
5. Click "Run Prompt"

**Expected Result:**
- ✅ Success message
- ✅ Response text displayed
- ✅ No error about `max_completion_tokens`

**What Was Fixed:**
- Changed ModelRegistry to use `max_output_tokens` (not `max_completion_tokens`)
- Applied to gpt-5, gpt-5-mini, gpt-5-nano

---

### Test Case 5: OpenAI gpt-3.5-turbo-0125 ✅
**Before:** ✅ Already working

**Test Steps:**
1. Select Provider: `OpenAI`
2. Select Model: `gpt-3.5-turbo-0125`
3. Type: `Text Generation`
4. Prompt: `say hello`
5. Click "Run Prompt"

**Expected Result:**
- ✅ Success message (should still work)
- ✅ Response text displayed
- ✅ No regression

**What Was Fixed:**
- Nothing (this was already working)
- Verified backward compatibility maintained

---

## Additional Test Cases

### Test Case 6: Parameter Variations
**Test with max_tokens specified:**
1. Go to Settings > Provider Options
2. Set `max_tokens` to 100
3. Run test prompt
4. Verify response is truncated appropriately

**Test with max_tokens empty:**
1. Clear `max_tokens` field
2. Run test prompt
3. Verify default value is used (4096 for Anthropic)

### Test Case 7: All Anthropic Models
Test each Claude model:
- `claude-sonnet-4-20250514`
- `claude-3-7-sonnet-20250219`
- `claude-opus-4-1-20250805`
- `claude-opus-4-20250514`
- `claude-3-5-haiku-20241022`
- `claude-3-haiku-20240307`

**Expected:** All should work without `max_tokens` error

### Test Case 8: All OpenAI Responses API Models
Test each model using Responses API:
- `gpt-5`
- `gpt-5-mini`
- `gpt-5-nano`
- `gpt-4.1`
- `gpt-4.1-mini`
- `gpt-4o`
- `gpt-4o-mini`
- `o3`
- `o3-mini`
- `o4-mini`

**Expected:** All should work without `missing choices array` error

### Test Case 9: All OpenAI Chat Completions Models
Test legacy models:
- `gpt-3.5-turbo`
- `gpt-3.5-turbo-0125`
- `gpt-4`

**Expected:** All should continue to work (backward compatibility)

### Test Case 10: Provider Switching
1. Run prompt with OpenAI
2. Switch to Anthropic
3. Run same prompt
4. Switch to Gemini
5. Run same prompt
6. Switch to Grok
7. Run same prompt

**Expected:** All providers work correctly, no cross-contamination

---

## Response Validation

For each successful test, verify:
- ✅ Response text is displayed
- ✅ No error messages
- ✅ Usage statistics are captured (if enabled)
- ✅ Response time is reasonable
- ✅ Content is relevant to prompt

---

## Error Handling

Test error scenarios:
1. **Invalid API Key:** Should show clear error message
2. **Network Error:** Should handle gracefully
3. **Rate Limit:** Should show appropriate message
4. **Empty Prompt:** Should validate before sending

---

## Browser Compatibility

Test in:
- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari

---

## Performance

Monitor:
- Response time for each provider
- Memory usage
- No JavaScript errors in console
- No PHP errors in debug log

---

## Regression Testing

Verify existing features still work:
1. ✅ API key save/load
2. ✅ Model discovery
3. ✅ Provider selection
4. ✅ Settings persistence
5. ✅ Prompt Library
6. ✅ Usage statistics

---

## Sign-Off

| Test Case | Status | Tester | Date | Notes |
|-----------|--------|--------|------|-------|
| Anthropic Claude Sonnet 4.5 | ⏳ | | | |
| OpenAI gpt-4.1 | ⏳ | | | |
| OpenAI o3 | ⏳ | | | |
| OpenAI gpt-5 | ⏳ | | | |
| OpenAI gpt-3.5-turbo-0125 | ⏳ | | | |
| Parameter Variations | ⏳ | | | |
| All Anthropic Models | ⏳ | | | |
| All OpenAI Responses Models | ⏳ | | | |
| All OpenAI Chat Models | ⏳ | | | |
| Provider Switching | ⏳ | | | |
| Error Handling | ⏳ | | | |
| Browser Compatibility | ⏳ | | | |
| Performance | ⏳ | | | |
| Regression Testing | ⏳ | | | |

---

## Deployment Checklist

- [ ] All critical tests pass
- [ ] No console errors
- [ ] No PHP errors
- [ ] Documentation updated
- [ ] Version number incremented
- [ ] Changelog updated
- [ ] Git commit with clear message
- [ ] Push to repository
- [ ] Deploy to production
- [ ] Smoke test on production
- [ ] Monitor for errors

---

## Rollback Plan

If issues are found:
1. Revert to version 0.0.9
2. Document the issue
3. Fix in development
4. Re-test
5. Re-deploy

---

## Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Check WordPress debug log for PHP errors
3. Verify API keys are valid
4. Test with simple prompt first
5. Contact support with error details

