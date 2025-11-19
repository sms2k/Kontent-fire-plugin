# 🔥 Kontent Fire Portal Integration Guide
## For kontent-fire-dev Plugin

This guide shows you how to connect ANY WordPress plugin to your Kontent Fire Portal.

---

## Step 1: Get the Plugin

On your computer, open Terminal and run:

```bash
cd /home/user
git clone https://github.com/sms2k/kontent-fire-dev.git
cd kontent-fire-dev
```

---

## Step 2: Check What You Have

Look for these files in your plugin:

**License Management:**
- Does it have a file with "license" in the name?
- Look in folders like: `includes/`, `lib/`, `classes/`

**API Calls:**
- Does it call Claude, OpenAI, or Gemini APIs?
- Look for files with "api", "claude", "openai", "gemini" in names

**Credit System:**
- Does it track credits or usage?
- Look for "credit", "usage", "quota" in file names

---

## Step 3: Create the Portal Integration Files

Copy these files into your plugin. Create folders if they don't exist.

### File 1: License Manager (`includes/portal-license-manager.php`)

```php
<?php
/**
 * Portal License Manager
 * Connects your plugin to Kontent Fire Portal
 */

class KF_Portal_License_Manager {

    private $portal_url = 'https://app.kontentfire.com/api';

    /**
     * Validate license key with portal
     */
    public function validate_license($license_key) {
        if (empty($license_key)) {
            return array('valid' => false, 'message' => 'License key required');
        }

        // Check for test keys
        $test_keys = array(
            'TEST-FULL-ACCESS-2024' => 'enterprise',
            'TEST-PRO-LICENSE-2024' => 'pro',
            'TEST-BASIC-LICENSE-2024' => 'basic',
        );

        if (isset($test_keys[$license_key])) {
            update_option('kf_license_key', $license_key);
            update_option('kf_license_status', 'active');
            update_option('kf_test_mode', true);
            return array(
                'valid' => true,
                'message' => 'Test license activated!',
                'data' => array('plan_type' => $test_keys[$license_key])
            );
        }

        // Validate with portal
        $response = wp_remote_post($this->portal_url . '/license/validate', array(
            'body' => json_encode(array(
                'license_key' => $license_key,
                'site_url' => get_site_url()
            )),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array('valid' => false, 'message' => 'Cannot connect to portal');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($data['valid']) {
            update_option('kf_license_key', $license_key);
            update_option('kf_license_status', 'active');
        }

        return $data;
    }

    /**
     * Activate license for this site
     */
    public function activate_license($license_key) {
        $response = wp_remote_post($this->portal_url . '/license/activate', array(
            'body' => json_encode(array(
                'license_key' => $license_key,
                'site_url' => get_site_url(),
                'site_name' => get_bloginfo('name')
            )),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Cannot connect to portal');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($data['success']) {
            update_option('kf_license_key', $license_key);
            update_option('kf_license_status', 'active');
        }

        return $data;
    }

    /**
     * Check if license is active
     */
    public function is_active() {
        return get_option('kf_license_status') === 'active';
    }

    /**
     * Get stored license key
     */
    public function get_license_key() {
        return get_option('kf_license_key');
    }
}
```

### File 2: Credit Manager (`includes/portal-credit-manager.php`)

```php
<?php
/**
 * Portal Credit Manager
 * Tracks and deducts credits through portal
 */

class KF_Portal_Credit_Manager {

    private $portal_url = 'https://app.kontentfire.com/api/';

    /**
     * Get current credit balance
     */
    public function get_balance() {
        // Test mode - unlimited credits
        if (get_option('kf_test_mode')) {
            return array(
                'success' => true,
                'credits' => 999999,
                'plan' => 'enterprise',
                'test_mode' => true
            );
        }

        $license_key = get_option('kf_license_key');
        if (!$license_key) {
            return array('success' => false, 'credits' => 0);
        }

        // Check cache (5 minutes)
        $cache_key = 'kf_credits_' . md5($license_key);
        $cached = get_transient($cache_key);
        if ($cached) {
            return $cached;
        }

        // Get from portal
        $response = wp_remote_get($this->portal_url . 'credits/balance', array(
            'headers' => array(
                'X-License-Key' => $license_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'credits' => 0);
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($data['success']) {
            set_transient($cache_key, $data, 300); // Cache 5 minutes
        }

        return $data;
    }

    /**
     * Deduct credits for an operation
     */
    public function deduct($operation, $quantity = 1, $metadata = array()) {
        // Test mode - don't deduct
        if (get_option('kf_test_mode')) {
            return array(
                'success' => true,
                'credits_deducted' => 0,
                'remaining_credits' => 999999,
                'test_mode' => true
            );
        }

        $license_key = get_option('kf_license_key');
        if (!$license_key) {
            return array('success' => false, 'message' => 'No license key');
        }

        $response = wp_remote_post($this->portal_url . 'credits/deduct', array(
            'body' => json_encode(array(
                'operation' => $operation,
                'quantity' => $quantity,
                'metadata' => $metadata
            )),
            'headers' => array(
                'X-License-Key' => $license_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Connection failed');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Clear cache
        delete_transient('kf_credits_' . md5($license_key));

        return $data;
    }

    /**
     * Check if enough credits available
     */
    public function has_credits($amount = 1) {
        $balance = $this->get_balance();
        return $balance['success'] && $balance['credits'] >= $amount;
    }

    /**
     * Get upgrade URL
     */
    public function get_upgrade_url() {
        return 'https://app.kontentfire.com/dashboard/billing';
    }
}
```

### File 3: AI API Proxy (`includes/portal-ai-proxy.php`)

```php
<?php
/**
 * Portal AI Proxy
 * Routes AI API calls through portal with automatic credit tracking
 */

class KF_Portal_AI_Proxy {

    private $portal_url = 'https://app.kontentfire.com/api/proxy/';

    /**
     * Call Claude API through portal
     */
    public function call_claude($messages, $options = array()) {
        $license_key = get_option('kf_license_key');
        if (!$license_key) {
            return array('success' => false, 'message' => 'No license key');
        }

        $body = array(
            'model' => $options['model'] ?? 'claude-sonnet-4-5-20250929',
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'temperature' => $options['temperature'] ?? 0.7
        );

        if (isset($options['system'])) {
            $body['system'] = $options['system'];
        }

        $response = wp_remote_post($this->portal_url . 'claude', array(
            'body' => json_encode($body),
            'headers' => array(
                'X-License-Key' => $license_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($data['success']) {
            return array(
                'success' => true,
                'content' => $data['data']['content'][0]['text'] ?? '',
                'usage' => $data['usage']
            );
        }

        return $data;
    }

    /**
     * Call OpenAI API through portal
     */
    public function call_openai($endpoint, $params) {
        $license_key = get_option('kf_license_key');
        if (!$license_key) {
            return array('success' => false, 'message' => 'No license key');
        }

        $body = array_merge(array('endpoint' => $endpoint), $params);

        $response = wp_remote_post($this->portal_url . 'openai', array(
            'body' => json_encode($body),
            'headers' => array(
                'X-License-Key' => $license_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Call Gemini API through portal
     */
    public function call_gemini($model, $endpoint, $params) {
        $license_key = get_option('kf_license_key');
        if (!$license_key) {
            return array('success' => false, 'message' => 'No license key');
        }

        $body = array(
            'model' => $model,
            'endpoint' => $endpoint
        );
        $body = array_merge($body, $params);

        $response = wp_remote_post($this->portal_url . 'gemini', array(
            'body' => json_encode($body),
            'headers' => array(
                'X-License-Key' => $license_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Generate content with Claude (simple helper)
     */
    public function generate_content($prompt, $system_message = '') {
        $messages = array(
            array('role' => 'user', 'content' => $prompt)
        );

        $options = array();
        if ($system_message) {
            $options['system'] = $system_message;
        }

        return $this->call_claude($messages, $options);
    }

    /**
     * Generate image with DALL-E
     */
    public function generate_image($prompt, $size = '1024x1024') {
        return $this->call_openai('images.generate', array(
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'size' => $size,
            'n' => 1
        ));
    }
}
```

---

## Step 4: Add Portal Initialization

Add this to your main plugin file (the one with the plugin header):

```php
// Load portal integration
require_once plugin_dir_path(__FILE__) . 'includes/portal-license-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/portal-credit-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/portal-ai-proxy.php';

// Initialize portal managers
global $kf_license, $kf_credits, $kf_ai;
$kf_license = new KF_Portal_License_Manager();
$kf_credits = new KF_Portal_Credit_Manager();
$kf_ai = new KF_Portal_AI_Proxy();
```

---

## Step 5: Use Portal in Your Plugin

### Example: Check License Before Action

```php
// Before any feature
global $kf_license;
if (!$kf_license->is_active()) {
    wp_die('Please activate your license key in Settings → License');
}
```

### Example: Check Credits Before API Call

```php
// Before generating content
global $kf_credits;
if (!$kf_credits->has_credits(50)) {
    $upgrade_url = $kf_credits->get_upgrade_url();
    wp_die("Not enough credits! <a href='$upgrade_url'>Upgrade your plan</a>");
}
```

### Example: Generate Content with Claude

```php
// Old way (direct API call):
// $api_key = get_option('claude_api_key');
// $response = call_claude_directly($api_key, $prompt);

// New way (through portal - automatic credit tracking):
global $kf_ai;
$result = $kf_ai->generate_content(
    'Write a blog post about WordPress',
    'You are an expert content writer'
);

if ($result['success']) {
    $content = $result['content'];
    $credits_used = $result['usage']['credits_used'];
    echo "Generated! Used $credits_used credits. Remaining: {$result['usage']['credits_remaining']}";
}
```

### Example: Display Credits in Admin

```php
// Show credits in your settings page
global $kf_credits;
$balance = $kf_credits->get_balance();

if ($balance['success']) {
    echo "<div class='notice notice-info'>";
    echo "<p>💰 Credits: <strong>{$balance['credits']}</strong></p>";
    if (isset($balance['test_mode'])) {
        echo "<p>🧪 Test Mode Active (Unlimited Credits)</p>";
    }
    echo "</div>";
}
```

---

## Step 6: Add License Settings Page

Add this to create an admin page for license activation:

```php
// Add menu item
add_action('admin_menu', function() {
    add_options_page(
        'Kontent Fire License',
        'KF License',
        'manage_options',
        'kontent-fire-license',
        'kf_license_page'
    );
});

// License settings page
function kf_license_page() {
    global $kf_license, $kf_credits;

    // Handle activation
    if (isset($_POST['activate_license'])) {
        $key = sanitize_text_field($_POST['license_key']);
        $result = $kf_license->activate_license($key);

        if ($result['success']) {
            echo "<div class='notice notice-success'><p>✅ License activated!</p></div>";
        } else {
            echo "<div class='notice notice-error'><p>❌ {$result['message']}</p></div>";
        }
    }

    // Show form
    $current_key = $kf_license->get_license_key();
    $is_active = $kf_license->is_active();
    $balance = $kf_credits->get_balance();

    ?>
    <div class="wrap">
        <h1>🔥 Kontent Fire License</h1>

        <?php if ($is_active): ?>
            <div class="notice notice-success">
                <p>✅ <strong>License Active</strong></p>
                <p>License Key: <code><?php echo esc_html($current_key); ?></code></p>
            </div>

            <?php if ($balance['success']): ?>
                <div class="card">
                    <h2>💰 Credit Balance</h2>
                    <p style="font-size: 32px; margin: 10px 0;">
                        <strong><?php echo number_format($balance['credits']); ?></strong> credits
                    </p>
                    <?php if (isset($balance['test_mode'])): ?>
                        <p><em>🧪 Test Mode - Unlimited Credits</em></p>
                    <?php endif; ?>
                    <p>Plan: <strong><?php echo ucfirst($balance['plan']); ?></strong></p>
                    <p>
                        <a href="<?php echo $kf_credits->get_upgrade_url(); ?>"
                           class="button button-primary" target="_blank">
                            Manage Subscription
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>License Key</th>
                        <td>
                            <input type="text" name="license_key"
                                   class="regular-text"
                                   placeholder="KF-XXXX-XXXX-XXXX"
                                   value="<?php echo esc_attr($current_key); ?>">
                            <p class="description">
                                Enter your license key from
                                <a href="https://app.kontentfire.com/dashboard/licenses" target="_blank">
                                    app.kontentfire.com
                                </a>
                            </p>
                            <p class="description">
                                <strong>Test Keys:</strong><br>
                                Basic: <code>TEST-BASIC-LICENSE-2024</code><br>
                                Pro: <code>TEST-PRO-LICENSE-2024</code><br>
                                Enterprise: <code>TEST-FULL-ACCESS-2024</code>
                            </p>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="submit" name="activate_license" class="button button-primary">
                        Activate License
                    </button>
                </p>
            </form>
        <?php endif; ?>

        <hr>

        <h2>Need Help?</h2>
        <ul>
            <li>📝 <a href="https://app.kontentfire.com/register" target="_blank">Sign up for an account</a></li>
            <li>💳 <a href="https://app.kontentfire.com/dashboard/billing" target="_blank">Choose a plan</a></li>
            <li>🔑 <a href="https://app.kontentfire.com/dashboard/licenses" target="_blank">Get your license key</a></li>
            <li>📊 <a href="https://app.kontentfire.com/dashboard/usage" target="_blank">View usage analytics</a></li>
        </ul>
    </div>
    <?php
}
```

---

## Step 7: Replace Existing API Calls

Find anywhere in your plugin that calls AI APIs directly and replace them:

### Before (Direct API Call):
```php
$api_key = get_option('my_claude_key');
$ch = curl_init('https://api.anthropic.com/v1/messages');
// ... lots of curl code ...
```

### After (Portal Proxy):
```php
global $kf_ai;
$result = $kf_ai->generate_content($prompt, $system_message);
```

---

## Step 8: Test Everything

1. **Test License Activation:**
   - Go to WordPress admin → Settings → KF License
   - Enter test key: `TEST-PRO-LICENSE-2024`
   - Click "Activate License"
   - Should say "License activated!"

2. **Test Credit Display:**
   - Should show "999999 credits" in test mode

3. **Test AI Generation:**
   - Try generating content
   - Should work without entering API keys
   - Credits automatically tracked in portal

4. **Test Real License:**
   - Sign up at https://app.kontentfire.com/register
   - Choose a plan
   - Get your real license key
   - Activate it in WordPress
   - Generate content
   - Check https://app.kontentfire.com/dashboard/usage to see it logged!

---

## Quick Reference

### Check if license is active:
```php
global $kf_license;
if ($kf_license->is_active()) {
    // Good to go!
}
```

### Check credits:
```php
global $kf_credits;
$balance = $kf_credits->get_balance();
echo $balance['credits']; // e.g., 5000
```

### Deduct credits manually:
```php
global $kf_credits;
$result = $kf_credits->deduct('my_operation', 1, array(
    'description' => 'Generated blog post'
));
```

### Generate with Claude:
```php
global $kf_ai;
$result = $kf_ai->generate_content('Write about WordPress');
if ($result['success']) {
    echo $result['content'];
}
```

### Generate image:
```php
global $kf_ai;
$result = $kf_ai->generate_image('A futuristic robot');
if ($result['success']) {
    $image_url = $result['data']['data'][0]['url'];
}
```

---

## Portal URLs

All these are handled automatically, but for reference:

- License validation: `https://app.kontentfire.com/api/license/validate`
- License activation: `https://app.kontentfire.com/api/license/activate`
- Credit balance: `https://app.kontentfire.com/api/credits/balance`
- Credit deduction: `https://app.kontentfire.com/api/credits/deduct`
- Claude proxy: `https://app.kontentfire.com/api/proxy/claude`
- OpenAI proxy: `https://app.kontentfire.com/api/proxy/openai`
- Gemini proxy: `https://app.kontentfire.com/api/proxy/gemini`

---

## You're Done! 🎉

Your plugin now:
- ✅ Validates licenses through portal
- ✅ Tracks credits automatically
- ✅ Routes AI calls through portal (no API keys needed in WordPress!)
- ✅ Logs all usage for analytics
- ✅ Shows upgrade prompts when credits run low

Users just need to:
1. Sign up at app.kontentfire.com
2. Choose a plan
3. Copy their license key
4. Paste it into WordPress
5. Start creating content!
