#!/bin/bash

# 🔥 Kontent Fire Portal Integration Script
# This script automatically integrates your WordPress plugin with the portal

echo "🔥 Kontent Fire Portal Integration"
echo "=================================="
echo ""

# Ask for plugin directory
read -p "Enter the path to your WordPress plugin directory: " PLUGIN_DIR

if [ ! -d "$PLUGIN_DIR" ]; then
    echo "❌ Error: Directory not found: $PLUGIN_DIR"
    exit 1
fi

echo "✅ Found plugin directory: $PLUGIN_DIR"
echo ""

# Create includes directory if it doesn't exist
mkdir -p "$PLUGIN_DIR/includes"

# Copy portal integration files
echo "📁 Creating portal integration files..."

# File 1: License Manager
cat > "$PLUGIN_DIR/includes/portal-license-manager.php" << 'PHPEOF'
<?php
/**
 * Portal License Manager
 * Connects your plugin to Kontent Fire Portal
 */

class KF_Portal_License_Manager {

    private $portal_url = 'https://app.kontentfire.com/api';

    public function validate_license($license_key) {
        if (empty($license_key)) {
            return array('valid' => false, 'message' => 'License key required');
        }

        // Test keys
        $test_keys = array(
            'TEST-FULL-ACCESS-2024' => 'enterprise',
            'TEST-PRO-LICENSE-2024' => 'pro',
            'TEST-BASIC-LICENSE-2024' => 'basic',
        );

        if (isset($test_keys[$license_key])) {
            update_option('kf_license_key', $license_key);
            update_option('kf_license_status', 'active');
            update_option('kf_test_mode', true);
            return array('valid' => true, 'message' => 'Test license activated!', 'data' => array('plan_type' => $test_keys[$license_key]));
        }

        $response = wp_remote_post($this->portal_url . '/license/validate', array(
            'body' => json_encode(array('license_key' => $license_key, 'site_url' => get_site_url())),
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

    public function activate_license($license_key) {
        $response = wp_remote_post($this->portal_url . '/license/activate', array(
            'body' => json_encode(array('license_key' => $license_key, 'site_url' => get_site_url(), 'site_name' => get_bloginfo('name'))),
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

    public function is_active() {
        return get_option('kf_license_status') === 'active';
    }

    public function get_license_key() {
        return get_option('kf_license_key');
    }
}
PHPEOF

echo "  ✅ Created portal-license-manager.php"

# File 2: Credit Manager
cat > "$PLUGIN_DIR/includes/portal-credit-manager.php" << 'PHPEOF'
<?php
/**
 * Portal Credit Manager
 */

class KF_Portal_Credit_Manager {

    private $portal_url = 'https://app.kontentfire.com/api/';

    public function get_balance() {
        if (get_option('kf_test_mode')) {
            return array('success' => true, 'credits' => 999999, 'plan' => 'enterprise', 'test_mode' => true);
        }

        $license_key = get_option('kf_license_key');
        if (!$license_key) return array('success' => false, 'credits' => 0);

        $cache_key = 'kf_credits_' . md5($license_key);
        $cached = get_transient($cache_key);
        if ($cached) return $cached;

        $response = wp_remote_get($this->portal_url . 'credits/balance', array(
            'headers' => array('X-License-Key' => $license_key, 'Content-Type' => 'application/json'),
            'timeout' => 15
        ));

        if (is_wp_error($response)) return array('success' => false, 'credits' => 0);

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($data['success']) set_transient($cache_key, $data, 300);

        return $data;
    }

    public function deduct($operation, $quantity = 1, $metadata = array()) {
        if (get_option('kf_test_mode')) {
            return array('success' => true, 'credits_deducted' => 0, 'remaining_credits' => 999999, 'test_mode' => true);
        }

        $license_key = get_option('kf_license_key');
        if (!$license_key) return array('success' => false, 'message' => 'No license key');

        $response = wp_remote_post($this->portal_url . 'credits/deduct', array(
            'body' => json_encode(array('operation' => $operation, 'quantity' => $quantity, 'metadata' => $metadata)),
            'headers' => array('X-License-Key' => $license_key, 'Content-Type' => 'application/json'),
            'timeout' => 15
        ));

        if (is_wp_error($response)) return array('success' => false, 'message' => 'Connection failed');

        delete_transient('kf_credits_' . md5($license_key));
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function has_credits($amount = 1) {
        $balance = $this->get_balance();
        return $balance['success'] && $balance['credits'] >= $amount;
    }

    public function get_upgrade_url() {
        return 'https://app.kontentfire.com/dashboard/billing';
    }
}
PHPEOF

echo "  ✅ Created portal-credit-manager.php"

# File 3: AI Proxy
cat > "$PLUGIN_DIR/includes/portal-ai-proxy.php" << 'PHPEOF'
<?php
/**
 * Portal AI Proxy
 */

class KF_Portal_AI_Proxy {

    private $portal_url = 'https://app.kontentfire.com/api/proxy/';

    public function call_claude($messages, $options = array()) {
        $license_key = get_option('kf_license_key');
        if (!$license_key) return array('success' => false, 'message' => 'No license key');

        $body = array(
            'model' => $options['model'] ?? 'claude-sonnet-4-5-20250929',
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'temperature' => $options['temperature'] ?? 0.7
        );

        if (isset($options['system'])) $body['system'] = $options['system'];

        $response = wp_remote_post($this->portal_url . 'claude', array(
            'body' => json_encode($body),
            'headers' => array('X-License-Key' => $license_key, 'Content-Type' => 'application/json'),
            'timeout' => 60
        ));

        if (is_wp_error($response)) return array('success' => false, 'message' => $response->get_error_message());

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($data['success']) {
            return array('success' => true, 'content' => $data['data']['content'][0]['text'] ?? '', 'usage' => $data['usage']);
        }
        return $data;
    }

    public function generate_content($prompt, $system_message = '') {
        $messages = array(array('role' => 'user', 'content' => $prompt));
        $options = array();
        if ($system_message) $options['system'] = $system_message;
        return $this->call_claude($messages, $options);
    }

    public function call_openai($endpoint, $params) {
        $license_key = get_option('kf_license_key');
        if (!$license_key) return array('success' => false, 'message' => 'No license key');

        $body = array_merge(array('endpoint' => $endpoint), $params);

        $response = wp_remote_post($this->portal_url . 'openai', array(
            'body' => json_encode($body),
            'headers' => array('X-License-Key' => $license_key, 'Content-Type' => 'application/json'),
            'timeout' => 60
        ));

        if (is_wp_error($response)) return array('success' => false, 'message' => $response->get_error_message());
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function generate_image($prompt, $size = '1024x1024') {
        return $this->call_openai('images.generate', array('model' => 'dall-e-3', 'prompt' => $prompt, 'size' => $size, 'n' => 1));
    }
}
PHPEOF

echo "  ✅ Created portal-ai-proxy.php"

echo ""
echo "✅ Portal integration files created!"
echo ""
echo "📝 Next Steps:"
echo "=============="
echo ""
echo "1. Add this to your main plugin file (the one with 'Plugin Name:' at the top):"
echo ""
echo "   // Load Kontent Fire Portal Integration"
echo "   require_once plugin_dir_path(__FILE__) . 'includes/portal-license-manager.php';"
echo "   require_once plugin_dir_path(__FILE__) . 'includes/portal-credit-manager.php';"
echo "   require_once plugin_dir_path(__FILE__) . 'includes/portal-ai-proxy.php';"
echo ""
echo "   // Initialize portal managers"
echo "   global \$kf_license, \$kf_credits, \$kf_ai;"
echo "   \$kf_license = new KF_Portal_License_Manager();"
echo "   \$kf_credits = new KF_Portal_Credit_Manager();"
echo "   \$kf_ai = new KF_Portal_AI_Proxy();"
echo ""
echo "2. Test with this license key: TEST-PRO-LICENSE-2024"
echo ""
echo "3. Full documentation: /home/user/PLUGIN-INTEGRATION-GUIDE.md"
echo ""
echo "🔥 Done! Your plugin is ready for the portal!"
