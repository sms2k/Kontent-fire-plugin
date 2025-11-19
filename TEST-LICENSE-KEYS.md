# Test License Keys - Development & Testing Guide

## Available Test License Keys

For development and testing purposes, you can use these special license keys that work without a licensing server:

### Enterprise Plan (Full Access)
```
TEST-FULL-ACCESS-2024
```
- ✅ All features unlocked
- ✅ Unlimited credits (999,999)
- ✅ No server validation required
- ✅ Valid for 10 years

### Professional Plan
```
TEST-PRO-LICENSE-2024
```
- ✅ Pro features unlocked
- ✅ Unlimited credits
- ✅ No server validation

### Basic Plan
```
TEST-BASIC-LICENSE-2024
```
- ✅ Basic features only
- ✅ Unlimited credits
- ✅ No server validation

### Demo License
```
DEMO-LICENSE-KEY
```
- ✅ Pro plan features
- ✅ Perfect for demos and testing

---

## How to Use Test License Keys

### Step 1: Activate Plugin
1. Install and activate the Kontent Fire plugin
2. Go to: **WordPress Admin → Kontent Fire**

### Step 2: Enter Test License Key
1. Go to: **Kontent Fire → Settings**
2. Find the "License Key" field
3. Enter: `TEST-FULL-ACCESS-2024`
4. Click **"Activate License"**

### Step 3: Verify Activation
You should see:
- ✅ "Test license activated successfully! (Development Mode)"
- ✅ License Status: **Active**
- ✅ Plan: **Enterprise**
- ✅ Credits: **999,999**

---

## Test Mode Features

When using a test license key, the plugin operates in **Test Mode**:

### Unlimited Credits
- All operations are free (0 credits deducted)
- Balance always shows 999,999 credits
- No credit limits or restrictions

### No Server Communication
- No connection to licensing server required
- Works completely offline
- Instant activation

### All Features Unlocked
- Content generation (Claude, OpenAI, Gemini)
- Image generation (Imagen 4, DALL-E 3)
- Video generation (Veo 3)
- Auto-blogging with SEO
- Social media posting (all platforms)
- Advanced analytics
- Priority features

### Local Logging
- Usage is still logged locally in WordPress database
- You can track what operations you're testing
- Useful for debugging and analytics

---

## Testing Scenarios

### Test Auto-Blogging
```
License: TEST-FULL-ACCESS-2024
Go to: Kontent Fire → Auto-Blog
Configure: Business info, industry, ZIP codes
Generate: Click "Generate Blog Now"
Result: Full blog created with 0 credits deducted
```

### Test Social Media Posting
```
License: TEST-PRO-LICENSE-2024
Go to: Kontent Fire → Platforms
Connect: Social accounts (OAuth)
Post: Create content and schedule
Result: Posts created without credit charges
```

### Test Image Generation
```
License: TEST-FULL-ACCESS-2024
Go to: Kontent Fire → Media Studio
Generate: Create images with Imagen 4
Result: Unlimited image generation
```

### Test Credit System
```
License: TEST-BASIC-LICENSE-2024
Go to: Kontent Fire → Dashboard
View: Credit balance (999,999)
Perform: Any operation
Check: Credits remain unlimited
```

---

## Switching Between Test and Production

### From Test to Production
1. Go to: **Kontent Fire → Settings**
2. Enter your **real license key** from the licensing server
3. Click **"Activate License"**
4. Test mode automatically disables
5. Credit tracking becomes active

### From Production to Test
1. Go to: **Kontent Fire → Settings**
2. Click **"Deactivate License"**
3. Enter: `TEST-FULL-ACCESS-2024`
4. Click **"Activate License"**
5. Back in test mode

---

## Checking If Test Mode Is Active

### In WordPress Admin
Look for **"(Development Mode)"** in license status messages

### Via Dashboard Widget
- Test mode shows: **"Test Mode Active"**
- Credits show: **999,999** (unlimited)
- Plan shows with **(Test)** suffix

### In Database
```sql
SELECT option_value
FROM wp_options
WHERE option_name = 'kf_test_mode';
-- Returns: 1 (true) if test mode active
```

---

## API Key Configuration

### Test Mode Behavior

When using test license keys, you have two options:

#### Option 1: No API Keys (Mock Mode)
- Leave API key fields empty
- Plugin will use mock/simulated responses
- Perfect for UI/UX testing without API costs
- Content will be placeholder text

#### Option 2: Your Own API Keys (Real Testing)
- Add your Claude/OpenAI/Gemini API keys
- Plugin will make real API calls
- You pay API costs directly to providers
- Full functionality testing with real AI
- **Recommended for comprehensive testing**

**Add API Keys at:** Kontent Fire → Settings
- Claude API Key (Anthropic)
- OpenAI API Key
- Gemini API Key
- Google Cloud Project ID

---

## Credit Cost Reference

Even though test mode gives unlimited credits, here are the actual costs for reference:

| Operation | Credits | Real Cost Estimate |
|-----------|---------|-------------------|
| Auto Blog (Full) | 30 | ~$0.50 worth of AI calls |
| Image (Imagen 4) | 10 | ~$0.04 per image |
| Image (DALL-E 3) | 8 | ~$0.04 per image |
| Video (Veo 3) | 50 | ~$0.40 per video |
| Claude Content | 5 | ~$0.05 per 1K tokens |
| OpenAI Content | 4 | ~$0.03 per 1K tokens |
| Gemini Content | 2 | ~$0.01 per 1K tokens |
| Local AI | 0.5 | Negligible |
| Social Post | 1 | API call only |
| Keyword Research | 3 | ~$0.03 |
| SEO Analysis | 2 | ~$0.02 |

**Pricing Example:** $29/month = ~1,000 credits = ~33 full auto-blogs

---

## Removing Test Mode

### Permanently Disable Test Keys

If you want to force production mode only (remove test keys):

1. Edit file: `includes/licensing/class-kontent-fire-license-manager.php`
2. Find the `$test_keys` array
3. Empty the array:
```php
private $test_keys = array();
```
4. Save and re-upload the file

---

## For Developers

### Adding Custom Test Keys

Edit `class-kontent-fire-license-manager.php`:

```php
private $test_keys = array(
    'YOUR-CUSTOM-KEY-HERE' => 'enterprise',
    'ANOTHER-TEST-KEY' => 'pro',
);
```

### Checking Test Mode in Code

```php
// Check if test mode is active
if (get_option('kf_test_mode', false)) {
    // Test mode logic
    echo "Running in test mode";
}
```

### Bypass Credit Checks

Test mode automatically bypasses credit checks in:
- `Kontent_Fire_Credit_Manager->has_credits()`
- `Kontent_Fire_Credit_Manager->deduct_credits()`
- `Kontent_Fire_API_Proxy->*()` methods

---

## Important Notes

### Security
- Test keys should only be used in development/staging
- Remove test keys before deploying to production
- Never share test keys publicly (though they're in code)

### Limitations
- Test mode doesn't connect to the licensing server
- OAuth social connections still require real credentials
- API calls (if using real keys) still cost money
- Test mode is for plugin testing, not production use

### Best Practices
1. Use test keys for initial setup and UI testing
2. Switch to real license for production testing
3. Test all features with test key first
4. Verify credit deduction with real license
5. Document any issues found during testing

---

## Troubleshooting

### Test Key Not Working?
- Ensure you typed it exactly as shown (case-sensitive)
- Check for extra spaces before/after the key
- Try deactivating and reactivating the plugin
- Clear WordPress transients/cache

### Still Showing "Invalid License"?
- Make sure you're using the latest plugin version
- Check that the file `class-kontent-fire-license-manager.php` has been uploaded
- Verify the `$test_keys` array exists in the file

### Credits Not Showing as Unlimited?
- Check if `kf_test_mode` option is set to true
- Clear WordPress cache
- Check the Credit Manager class has test mode checks

---

## Support

For testing issues:
1. Check this documentation
2. Review plugin logs (WordPress debug.log)
3. Verify test key is in the `$test_keys` array
4. Contact: support@kynex.io

**Happy Testing! 🔥**
