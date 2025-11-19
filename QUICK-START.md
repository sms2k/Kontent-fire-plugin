# 🚀 Quick Start - Connect Your Plugin to Portal

## Super Simple 3-Step Process

### Step 1: Get Your Plugin
```bash
cd /home/user
git clone https://github.com/sms2k/kontent-fire-dev.git
```

### Step 2: Run the Integration Script
```bash
/home/user/integrate-plugin.sh
```

When asked for the plugin directory, enter:
```
/home/user/kontent-fire-dev
```

### Step 3: Add Portal to Your Plugin

Open your main plugin file (the one with "Plugin Name:" at the top) and add these lines:

```php
// Load Kontent Fire Portal Integration
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

## ✅ That's It!

Now you can use the portal in your code:

### Check License:
```php
global $kf_license;
if (!$kf_license->is_active()) {
    die('Please activate your license');
}
```

### Check Credits:
```php
global $kf_credits;
$balance = $kf_credits->get_balance();
echo "You have {$balance['credits']} credits";
```

### Generate Content with AI:
```php
global $kf_ai;
$result = $kf_ai->generate_content('Write a blog post about cats');
if ($result['success']) {
    echo $result['content'];
}
```

---

## 🧪 Test It

Use these test license keys:
- **Basic:** `TEST-BASIC-LICENSE-2024`
- **Pro:** `TEST-PRO-LICENSE-2024`
- **Enterprise:** `TEST-FULL-ACCESS-2024`

Test keys give you unlimited credits in test mode!

---

## 📚 Need More Help?

- **Full Guide:** `/home/user/PLUGIN-INTEGRATION-GUIDE.md`
- **Portal Setup:** `/home/user/kontent-fire-portal/DEPLOYMENT-GUIDE.md`
- **Easy Setup:** `/home/user/kontent-fire-portal/EASY-SETUP-GUIDE.md`

---

## 🎯 What the Portal Does

✅ No more API keys in WordPress
✅ Automatic credit tracking
✅ Usage analytics
✅ Subscription billing
✅ License management
✅ All AI calls logged

---

**Need to deploy the portal?**
Follow: `/home/user/kontent-fire-portal/EASY-SETUP-GUIDE.md`
