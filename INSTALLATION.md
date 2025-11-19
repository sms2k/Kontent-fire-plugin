# Installation & Setup Guide

## Quick Start

### Step 1: Install the Plugin

**Option A: Via WordPress Admin**
1. Download and zip the `kontent-fire-plugin` folder
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip file and activate

**Option B: Manual Installation**
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone <repository-url> kontent-fire
# Or copy the kontent-fire-plugin folder here
```

### Step 2: Activate the Plugin

1. Go to WordPress Admin → Plugins
2. Find "Kontent Fire by Kynex"
3. Click "Activate"
4. Database tables will be created automatically

### Step 3: Configure License

1. Navigate to **Kontent Fire → Settings**
2. Enter your license key (format: `KF-XXXXXXXX-XXXXXXXX-XXXXXXXX`)
3. Click "Validate License"
4. Wait for confirmation

### Step 4: Add API Keys

**You need at least ONE of these API keys:**

#### Claude API (Recommended for content generation)
1. Visit: https://console.anthropic.com/
2. Sign up or log in
3. Navigate to API Keys
4. Create new key
5. Copy and paste into Settings

#### OpenAI API (Required for DALL-E images)
1. Visit: https://platform.openai.com/
2. Sign up or log in
3. Navigate to API Keys
4. Create new secret key
5. Copy and paste into Settings

#### Google Gemini API (Optional, for video scripts)
1. Visit: https://makersuite.google.com/app/apikey
2. Sign in with Google account
3. Create new API key
4. Copy and paste into Settings

### Step 5: Connect Social Platforms

#### Facebook Setup
1. Go to https://developers.facebook.com/
2. Create an app
3. Add Facebook Login and Pages API products
4. Get App ID and App Secret
5. In Kontent Fire → Platforms → Connect Facebook
6. Complete OAuth flow
7. Select page to manage

#### Instagram Setup
1. Convert Instagram account to Business profile
2. Link to Facebook page
3. Use same Facebook App
4. In Kontent Fire → Platforms → Connect Instagram
5. Authorize and select account

#### Twitter/X Setup
1. Go to https://developer.twitter.com/
2. Create developer account
3. Create new app
4. Generate API keys and tokens
5. Enable OAuth 2.0
6. In Kontent Fire → Platforms → Connect Twitter
7. Complete authorization

#### LinkedIn Setup
1. Go to https://www.linkedin.com/developers/
2. Create new app
3. Add Products: Sign In with LinkedIn, Share on LinkedIn
4. Get Client ID and Client Secret
5. In Kontent Fire → Platforms → Connect LinkedIn
6. Complete OAuth flow

## Advanced Configuration

### Server Requirements

- **PHP Extensions**:
  - `json`
  - `curl`
  - `gd` or `imagick` (for image processing)
  - `mbstring`

- **WordPress Settings**:
  - Increase `max_execution_time` to at least 120 seconds
  - Set `memory_limit` to at least 256M

### Cron Jobs

The plugin uses WordPress cron for:
- Processing scheduled posts (every 5 minutes)
- Updating analytics (hourly)
- Refreshing platform tokens (daily)
- Cleaning temp files (daily)

To use system cron instead of WP-Cron:

1. Add to `wp-config.php`:
```php
define('DISABLE_WP_CRON', true);
```

2. Add to system crontab:
```bash
*/5 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

### Database Optimization

For high-volume sites, add indexes:

```sql
ALTER TABLE wp_kf_posts ADD INDEX idx_user_status (user_id, status);
ALTER TABLE wp_kf_analytics ADD INDEX idx_post_recorded (post_id, recorded_at);
```

## Troubleshooting

### Plugin Won't Activate
- Check PHP version (must be 7.4+)
- Check WordPress version (must be 5.8+)
- Review error logs: `wp-content/debug.log`

### API Errors
- Verify API keys are correct
- Check API quotas and billing
- Review API rate limits

### Platform Connection Issues
- Ensure OAuth redirect URLs are whitelisted
- Check that platform app is in production mode
- Verify all required permissions are granted

### Scheduled Posts Not Publishing
- Check if WP-Cron is running
- Verify server timezone settings
- Review queue status in dashboard

## Uninstallation

### Clean Uninstall (Removes All Data)

1. Deactivate the plugin
2. Delete from Plugins page
3. Database tables will be preserved

### Manual Database Cleanup

```sql
DROP TABLE IF EXISTS wp_kf_posts;
DROP TABLE IF EXISTS wp_kf_platform_connections;
DROP TABLE IF EXISTS wp_kf_templates;
DROP TABLE IF EXISTS wp_kf_analytics;
DROP TABLE IF EXISTS wp_kf_licenses;
DROP TABLE IF EXISTS wp_kf_content_pipeline;
DROP TABLE IF EXISTS wp_kf_seo_data;
```

Delete plugin options:
```sql
DELETE FROM wp_options WHERE option_name LIKE 'kontent_fire_%';
```

## Support

If you encounter issues:
1. Check this guide first
2. Review README.md
3. Contact: support@kynex.io
4. Include:
   - WordPress version
   - PHP version
   - Error messages
   - Steps to reproduce
