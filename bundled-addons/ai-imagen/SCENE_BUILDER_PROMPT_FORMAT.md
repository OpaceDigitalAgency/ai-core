# Scene Builder Prompt Format - Version 0.4.6

## Overview

The Scene Builder now generates prompts in a natural language format that follows strict resolution and positioning rules. This ensures AI models respect the selected aspect ratio and place overlays exactly where specified.

## Prompt Format

### Structure

```
[User's base prompt]. Canvas ratio and resolution are defined by the selected aspect ratio in the generation settings (do not infer or override). [Overlay instructions]. Follow these coordinates exactly relative to the canvas size, not the image content. Do not render or display these layout instructions or ratio text.
```

### Example Output

```
A luxury sports car on a mountain road. Canvas ratio and resolution are defined by the selected aspect ratio in the generation settings (do not infer or override). Add a text overlay with the text "Your Text Here" positioned 38% from the left and 38% from the top, taking up approximately 24% of the canvas width and 59% of the canvas height, in #000000 colour, 24px font size, normal weight. Add a heart icon overlay positioned 0% from the left and 0% from the top, sized at approximately 17% of the canvas width. Follow these coordinates exactly relative to the canvas size, not the image content. Do not render or display these layout instructions or ratio text.
```

## Resolution Handling Rules

### 1. Respect Dropdown Selection Only

- Use the chosen aspect ratio (1:1, 4:3, 16:9, or 9:16)
- Do not generate or infer any other ratio (e.g., 4.29:1)
- Treat all positional and size percentages as relative to the selected ratio's canvas area

### 2. Resolution Scaling

- Always calculate percentages relative to the final output resolution set by the aspect ratio, not absolute pixels
- Example: if the ratio is 4:3 at 1024×768, then:
  - 38% from left = 0.38 × 1024 = 389px
  - 38% from top = 0.38 × 768 = 292px

### 3. Fixed Anchor Rule

- The coordinates and dimensions apply to the **top-left corner** of each overlay box (not the text centre)
- Overlays must not stretch or centre themselves automatically
- Position is always measured from the top-left corner of the canvas

### 4. Don't Infer "Creative" Cropping or Placement

- If a car or background doesn't fit naturally, it should be resized within the frame
- Do not shift the overlays to accommodate the background image
- Overlays remain fixed at their specified positions regardless of image content

### 5. Never Render the Instructions or Ratio Text

- Everything after "Canvas ratio and resolution..." is meta-information
- Treat it as rendering logic, not visual content
- The AI should not display these instructions in the generated image

## Overlay Types

### Text Overlay

Format:
```
Add a text overlay with the text "[content]" positioned [X]% from the left and [Y]% from the top, taking up approximately [W]% of the canvas width and [H]% of the canvas height, in [color] colour, [size]px font size, [weight] weight
```

Properties:
- **Content**: The actual text to display
- **Position**: X% from left, Y% from top (top-left corner anchor)
- **Size**: Width and height as percentages of canvas
- **Color**: Hex colour code (e.g., #000000)
- **Font Size**: In pixels (e.g., 24px)
- **Font Weight**: normal, bold, etc.

Example:
```
Add a text overlay with the text "Summer Sale" positioned 10% from the left and 20% from the top, taking up approximately 80% of the canvas width and 15% of the canvas height, in #FF0000 colour, 48px font size, bold weight
```

### Icon Overlay

Format:
```
Add a [icon name] icon overlay positioned [X]% from the left and [Y]% from the top, sized at approximately [W]% of the canvas width
```

Properties:
- **Icon Name**: Description of the icon (e.g., heart, star, arrow)
- **Position**: X% from left, Y% from top (top-left corner anchor)
- **Size**: Width as percentage of canvas (height scales proportionally)

Example:
```
Add a heart icon overlay positioned 5% from the left and 5% from the top, sized at approximately 10% of the canvas width
```

### Logo Overlay

Format:
```
Add a logo overlay positioned [X]% from the left and [Y]% from the top, sized at approximately [W]% of the canvas width
```

Properties:
- **Position**: X% from left, Y% from top (top-left corner anchor)
- **Size**: Width as percentage of canvas (height scales proportionally)

Example:
```
Add a logo overlay positioned 85% from the left and 5% from the top, sized at approximately 10% of the canvas width
```

### Image Overlay

Format:
```
Add an image overlay positioned [X]% from the left and [Y]% from the top, sized at approximately [W]% of the canvas width
```

Properties:
- **Position**: X% from left, Y% from top (top-left corner anchor)
- **Size**: Width as percentage of canvas (height scales proportionally)

Example:
```
Add an image overlay positioned 50% from the left and 50% from the top, sized at approximately 30% of the canvas width
```

## Technical Implementation

### Code Location

File: `bundled-addons/ai-imagen/assets/js/scene-builder.js`
Function: `generateSceneDescription()`
Lines: 773-833

### Key Changes in v0.4.6

1. **Removed numbered list format** - Changed from "1. Use a 1:1 square canvas. 2. Place..." to natural language
2. **Added resolution handling instructions** - Explicitly states that aspect ratio is defined by settings
3. **Simplified overlay descriptions** - More natural language format
4. **Fixed font size formatting** - Ensures fontSize is always output with 'px' suffix
5. **Added strict positioning rules** - Clarifies that coordinates are relative to canvas, not content

### Percentage Calculation

```javascript
var xPercent = Math.round((el.x / canvasWidth) * 100);
var yPercent = Math.round((el.y / canvasHeight) * 100);
var widthPercent = Math.round((el.width / canvasWidth) * 100);
var heightPercent = Math.round((el.height / canvasHeight) * 100);
```

All percentages are rounded to whole numbers for clarity.

## AI Model Compatibility

### Tested Models

- ✅ OpenAI DALL-E 3
- ✅ OpenAI GPT-Image-1
- ✅ Google Gemini Imagen (gemini-2.5-flash-image variants)
- ✅ xAI Grok Image (grok-2-image-1212)

### Known Limitations

1. **Text rendering accuracy** - Some models may not render text exactly as specified
2. **Icon interpretation** - Icon descriptions may be interpreted differently by different models
3. **Positioning precision** - Some models may have ±5% positioning variance
4. **Font size** - Not all models support exact font size control

### Best Practices

1. **Use clear, descriptive text** - Avoid special characters or complex formatting
2. **Keep icon names simple** - Use common icon names (heart, star, arrow, etc.)
3. **Test with different models** - Results may vary between providers
4. **Use high contrast colours** - Ensures text is readable
5. **Avoid overlapping elements** - Position elements with adequate spacing

## Troubleshooting

### Issue: Overlays not appearing in correct position

**Solution:** Ensure the aspect ratio dropdown matches the intended output. The AI uses the selected aspect ratio to calculate absolute positions.

### Issue: Text not rendering

**Solution:** Some models have limitations on text rendering. Try:
- Simplifying the text
- Using a larger font size
- Increasing the text box size
- Using high contrast colours

### Issue: Icons not matching description

**Solution:** Use more specific icon descriptions or try different icon names. Consider using simple geometric shapes (circle, square, triangle) for more predictable results.

### Issue: Aspect ratio not respected

**Solution:** The prompt explicitly states "do not infer or override" the aspect ratio. If the model still generates incorrect ratios, this is a model limitation. Try:
- Using a different model
- Simplifying the base prompt
- Removing complex scene descriptions that might confuse the model

## Version History

### v0.4.6 (2025-10-08)
- Rewrote prompt format to natural language
- Added explicit resolution handling instructions
- Fixed font size formatting (always includes 'px')
- Removed numbered list format
- Added strict positioning rules

### v0.4.5 (2025-10-08)
- Fixed prompts not being added to database
- Fixed duplicate groups issue
- Fixed Base64 save to library error

### v0.4.4 (2025-10-08)
- Improved Scene Builder prompt format with numbered instructions
- Added Base64 data URL support

---

**Version:** 0.4.6  
**Date:** 2025-10-08  
**Author:** AI Core Development Team

