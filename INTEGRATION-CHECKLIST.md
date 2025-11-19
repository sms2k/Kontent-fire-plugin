# ✅ Integration Checklist

## Connect kontent-fire-dev to Portal

---

## Option 1: Automatic (Easiest!)

```bash
# Run this script:
/home/user/integrate-plugin.sh

# When asked, enter:
/home/user/kontent-fire-dev
```

Then skip to **Final Step** below!

---

## Option 2: Manual

### 1. Copy Portal Files to Your Plugin

Copy these 3 files:
```
FROM: /home/user/PLUGIN-INTEGRATION-GUIDE.md (see File 1, 2, 3)
TO: /home/user/kontent-fire-dev/includes/
```

Files to create:
- ✅ `portal-license-manager.php`
- ✅ `portal-credit-manager.php`
- ✅ `portal-ai-proxy.php`

### 2. Find Your Main Plugin File

Look for the file with this at the top:
```php
/**
 * Plugin Name: Something
 * Plugin URI: ...
 */
```

Usually named like:
- `kontent-fire.php`
- `plugin-name.php`
- `main.php`

### 3. Add Portal Code

Add this AFTER the plugin header but BEFORE any other code:

```php
// ===============================================
// Kontent Fire Portal Integration
// ===============================================

// Load portal classes
require_once plugin_dir_path(__FILE__) . 'includes/portal-license-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/portal-credit-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/portal-ai-proxy.php';

// Initialize managers (available everywhere as globals)
global $kf_license, $kf_credits, $kf_ai;
$kf_license = new KF_Portal_License_Manager();
$kf_credits = new KF_Portal_Credit_Manager();
$kf_ai = new KF_Portal_AI_Proxy();

// ===============================================
```

---

## Final Step (Required!)

### Add License Settings Page

Add this anywhere in your main plugin file:

```php
// License settings page
add_action('admin_menu', function() {
    add_options_page(
        'Kontent Fire License',
        'KF License',
        'manage_options',
        'kontent-fire-license',
        function() {
            global $kf_license, $kf_credits;

            // Handle form submission
            if (isset($_POST['activate_license'])) {
                $key = sanitize_text_field($_POST['license_key']);
                $result = $kf_license->activate_license($key);

                if ($result['success']) {
                    echo '<div class="notice notice-success"><p>✅ License activated!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ ' . esc_html($result['message']) . '</p></div>';
                }
            }

            $current_key = $kf_license->get_license_key();
            $is_active = $kf_license->is_active();
            $balance = $kf_credits->get_balance();
            ?>

            <div class="wrap">
                <h1>🔥 Kontent Fire License</h1>

                <?php if ($is_active): ?>
                    <div class="notice notice-success">
                        <p>✅ <strong>License Active</strong></p>
                        <p>Key: <code><?php echo esc_html($current_key); ?></code></p>
                    </div>

                    <?php if ($balance['success']): ?>
                        <div class="card" style="max-width: 400px; padding: 20px;">
                            <h2>💰 Credits</h2>
                            <p style="font-size: 48px; font-weight: bold; margin: 20px 0;">
                                <?php echo number_format($balance['credits']); ?>
                            </p>
                            <p>Plan: <strong><?php echo ucfirst($balance['plan']); ?></strong></p>
                            <?php if (isset($balance['test_mode'])): ?>
                                <p style="color: #666;"><em>🧪 Test Mode</em></p>
                            <?php endif; ?>
                            <p>
                                <a href="<?php echo $kf_credits->get_upgrade_url(); ?>"
                                   class="button button-primary" target="_blank">
                                    Manage Subscription →
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <form method="post">
                        <table class="form-table">
                            <tr>
                                <th>License Key</th>
                                <td>
                                    <input type="text" name="license_key"
                                           class="regular-text"
                                           placeholder="KF-XXXX-XXXX-XXXX"
                                           value="<?php echo esc_attr($current_key); ?>">
                                    <p class="description">
                                        Get your key from
                                        <a href="https://app.kontentfire.com/dashboard/licenses" target="_blank">
                                            app.kontentfire.com
                                        </a>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" name="activate_license" class="button button-primary">
                                Activate License
                            </button>
                        </p>

                        <hr>

                        <h3>🧪 Test Keys (Development)</h3>
                        <ul>
                            <li>Basic: <code>TEST-BASIC-LICENSE-2024</code></li>
                            <li>Pro: <code>TEST-PRO-LICENSE-2024</code></li>
                            <li>Enterprise: <code>TEST-FULL-ACCESS-2024</code></li>
                        </ul>
                    </form>
                <?php endif; ?>
            </div>
            <?php
        }
    );
});
```

---

## Usage Examples

### Before Running Any AI Feature:

```php
// Check license first
global $kf_license;
if (!$kf_license->is_active()) {
    wp_die('Please activate your license in Settings → KF License');
}

// Check credits
global $kf_credits;
if (!$kf_credits->has_credits(10)) {
    $url = $kf_credits->get_upgrade_url();
    wp_die("Not enough credits! <a href='$url'>Upgrade</a>");
}
```

### Generate Content:

```php
global $kf_ai;

$result = $kf_ai->generate_content(
    'Write a blog post about WordPress plugins',
    'You are an expert WordPress developer'
);

if ($result['success']) {
    $content = $result['content'];
    $credits_used = $result['usage']['credits_used'];
    echo "Used $credits_used credits";
} else {
    echo "Error: " . $result['message'];
}
```

### Generate Image:

```php
global $kf_ai;

$result = $kf_ai->generate_image('A futuristic robot', '1024x1024');

if ($result['success']) {
    $image_url = $result['data']['data'][0]['url'];
    echo "<img src='$image_url'>";
}
```

---

## Testing Checklist

### ✅ Test 1: Install Plugin
- [ ] Upload plugin to WordPress
- [ ] Activate plugin
- [ ] No errors appear

### ✅ Test 2: License Page
- [ ] Go to Settings → KF License
- [ ] Page loads correctly
- [ ] Shows license form

### ✅ Test 3: Activate Test License
- [ ] Enter: `TEST-PRO-LICENSE-2024`
- [ ] Click "Activate License"
- [ ] See "✅ License activated!"
- [ ] See credits: 999,999
- [ ] See "🧪 Test Mode"

### ✅ Test 4: Generate Content
- [ ] Try your plugin's AI features
- [ ] Content generates successfully
- [ ] No API key required!

### ✅ Test 5: Real License (Optional)
- [ ] Go to https://app.kontentfire.com/register
- [ ] Sign up and choose a plan
- [ ] Copy your real license key
- [ ] Activate in WordPress
- [ ] Generate content
- [ ] Check portal dashboard - usage should appear!

---

## Troubleshooting

### "Cannot connect to portal"
- Check your site has internet access
- Try: `curl https://app.kontentfire.com/api/license/validate`

### "No license key"
- Go to Settings → KF License
- Enter a test key first

### "Not enough credits"
- Using real license? Go to portal and check balance
- Using test key? Should have unlimited credits

### Portal not deployed yet?
- Follow: `/home/user/kontent-fire-portal/DEPLOYMENT-GUIDE.md`
- Takes about 2 hours for first setup
- OR use test keys while you deploy!

---

## File Locations Reference

```
Your Plugin Files:
├── kontent-fire.php (or similar)          ← Add portal code here
└── includes/
    ├── portal-license-manager.php         ← New file
    ├── portal-credit-manager.php          ← New file
    └── portal-ai-proxy.php                ← New file

Portal Files:
├── kontent-fire-portal/                   ← Deploy this to Vercel
│   ├── DEPLOYMENT-GUIDE.md                ← How to deploy
│   └── ...

Integration Help:
├── QUICK-START.md                         ← Quick overview
├── PLUGIN-INTEGRATION-GUIDE.md            ← Full guide
├── INTEGRATION-CHECKLIST.md               ← This file!
└── integrate-plugin.sh                    ← Automatic script
```

---

## Quick Links

- 🚀 Portal Dashboard: https://app.kontentfire.com
- 📝 Sign Up: https://app.kontentfire.com/register
- 🔑 License Keys: https://app.kontentfire.com/dashboard/licenses
- 💳 Billing: https://app.kontentfire.com/dashboard/billing
- 📊 Usage Stats: https://app.kontentfire.com/dashboard/usage

---

## Support

Questions? Check these files:
1. `/home/user/QUICK-START.md` - Simple overview
2. `/home/user/PLUGIN-INTEGRATION-GUIDE.md` - Detailed guide
3. `/home/user/kontent-fire-portal/DEPLOYMENT-GUIDE.md` - Deploy portal

---

**🔥 You're ready to integrate!**

Choose automatic (run the script) or manual (copy the files).
Either way takes about 5 minutes!
