<?php
/**
 * Platforms management page - OAuth connections
 */
if (!defined('WPINC')) {
    die;
}

// Get OAuth manager
$oauth_manager = new Kontent_Fire_OAuth_Manager();
$connected_platforms = $oauth_manager->get_connected_platforms();

// Check for OAuth success message
$oauth_success = isset($_GET['oauth_success']) ? sanitize_text_field($_GET['oauth_success']) : '';
$oauth_error = isset($_GET['oauth_error']) ? sanitize_text_field($_GET['oauth_error']) : '';

// Available platforms
$available_platforms = array(
    'facebook' => array(
        'name' => 'Facebook',
        'icon' => '📘',
        'color' => '#1877F2',
        'description' => 'Connect your Facebook Business Page',
        'account_type' => 'Business Page',
        'features' => array('Text posts', 'Images', 'Videos', 'Links')
    ),
    'instagram' => array(
        'name' => 'Instagram',
        'icon' => '📷',
        'color' => '#E4405F',
        'description' => 'Connect your Instagram Business Account',
        'account_type' => 'Business Account',
        'features' => array('Photos', 'Carousels', 'Reels', 'Stories')
    ),
    'twitter' => array(
        'name' => 'Twitter (X)',
        'icon' => '🐦',
        'color' => '#1DA1F2',
        'description' => 'Connect your Twitter/X account',
        'account_type' => 'Any Account',
        'features' => array('Tweets', 'Images', 'Videos', 'Threads')
    ),
    'linkedin' => array(
        'name' => 'LinkedIn',
        'icon' => '💼',
        'color' => '#0A66C2',
        'description' => 'Connect your LinkedIn Company Page',
        'account_type' => 'Company Page',
        'features' => array('Posts', 'Articles', 'Images', 'Videos')
    ),
    'tiktok' => array(
        'name' => 'TikTok',
        'icon' => '🎵',
        'color' => '#000000',
        'description' => 'Connect your TikTok Business Account',
        'account_type' => 'Business Account',
        'features' => array('Videos', 'Duets', 'Sounds')
    ),
    'youtube' => array(
        'name' => 'YouTube',
        'icon' => '▶️',
        'color' => '#FF0000',
        'description' => 'Connect your YouTube Channel',
        'account_type' => 'Channel',
        'features' => array('Videos', 'Shorts', 'Community posts')
    )
);
?>

<div class="wrap kf-platforms-page">
    <h1>🔗 Social Media Connections</h1>
    <p class="description">Connect your business social media accounts to publish content across multiple platforms.</p>

    <?php if ($oauth_success): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✅ Success!</strong> Your <?php echo esc_html(ucfirst($oauth_success)); ?> account has been connected.</p>
        </div>
    <?php endif; ?>

    <?php if ($oauth_error): ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>❌ Error:</strong> <?php echo esc_html($oauth_error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Connected Platforms -->
    <?php if (!empty($connected_platforms)): ?>
    <div class="kf-card" style="margin-bottom: 30px;">
        <h2>✅ Connected Accounts</h2>
        <div class="kf-connected-platforms">
            <?php foreach ($connected_platforms as $connection):
                $platform_info = $available_platforms[$connection->platform] ?? array();
            ?>
                <div class="kf-connected-platform">
                    <div class="platform-icon" style="background-color: <?php echo esc_attr($platform_info['color'] ?? '#666'); ?>;">
                        <?php echo $platform_info['icon'] ?? '🔗'; ?>
                    </div>
                    <div class="platform-info">
                        <h3><?php echo esc_html($platform_info['name'] ?? ucfirst($connection->platform)); ?></h3>
                        <p class="account-name"><?php echo esc_html($connection->account_name); ?></p>
                        <p class="connected-date">Connected: <?php echo date('M j, Y', strtotime($connection->created_at)); ?></p>
                    </div>
                    <div class="platform-actions">
                        <span class="status-badge status-active">● Active</span>
                        <button class="button kf-disconnect-platform"
                                data-platform="<?php echo esc_attr($connection->platform); ?>"
                                data-name="<?php echo esc_attr($connection->account_name); ?>">
                            Disconnect
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Available Platforms -->
    <div class="kf-card">
        <h2>📱 Available Platforms</h2>
        <p class="description" style="margin-bottom: 20px;">
            <strong>Important:</strong> Only business/professional accounts can be connected. Personal accounts are not supported for automated posting.
        </p>

        <div class="kf-platforms-grid">
            <?php foreach ($available_platforms as $platform_key => $platform):
                $is_connected = false;
                foreach ($connected_platforms as $conn) {
                    if ($conn->platform === $platform_key) {
                        $is_connected = true;
                        break;
                    }
                }
            ?>
                <div class="kf-platform-card <?php echo $is_connected ? 'connected' : ''; ?>">
                    <div class="platform-header" style="border-left: 4px solid <?php echo esc_attr($platform['color']); ?>;">
                        <div class="platform-icon-large"><?php echo $platform['icon']; ?></div>
                        <h3><?php echo esc_html($platform['name']); ?></h3>
                    </div>

                    <div class="platform-body">
                        <p class="platform-description"><?php echo esc_html($platform['description']); ?></p>

                        <div class="account-type-badge">
                            <strong>Account Type:</strong> <?php echo esc_html($platform['account_type']); ?>
                        </div>

                        <div class="platform-features">
                            <strong>Supports:</strong>
                            <ul>
                                <?php foreach ($platform['features'] as $feature): ?>
                                    <li>✓ <?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="platform-footer">
                        <?php if ($is_connected): ?>
                            <button class="button button-secondary" disabled>
                                ✅ Connected
                            </button>
                        <?php else: ?>
                            <button class="button button-primary kf-connect-platform"
                                    data-platform="<?php echo esc_attr($platform_key); ?>"
                                    data-name="<?php echo esc_attr($platform['name']); ?>">
                                Connect <?php echo esc_html($platform['name']); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Requirements Notice -->
    <div class="kf-card" style="margin-top: 30px; background: #FFF9E6; border-left: 4px solid #FFA500;">
        <h3>⚠️ Business Account Requirements</h3>
        <div style="line-height: 1.8;">
            <p><strong>Before connecting, ensure you have:</strong></p>
            <ul style="margin-left: 20px;">
                <li><strong>Facebook:</strong> A Facebook Business Page (not personal profile)</li>
                <li><strong>Instagram:</strong> A Business or Creator account (convert in Instagram settings)</li>
                <li><strong>Twitter:</strong> Any account works (personal or business)</li>
                <li><strong>LinkedIn:</strong> A Company Page (not personal profile)</li>
                <li><strong>TikTok:</strong> A Business Account (upgrade in TikTok settings)</li>
                <li><strong>YouTube:</strong> A YouTube Channel with content upload permissions</li>
            </ul>
            <p style="margin-top: 15px;"><strong>Why business accounts?</strong> Business accounts have API access required for automated posting. Personal accounts don't support this functionality.</p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Connect platform
    $('.kf-connect-platform').on('click', function() {
        var platform = $(this).data('platform');
        var name = $(this).data('name');
        var button = $(this);

        if (!confirm('Connect your ' + name + ' business account?\n\nYou will be redirected to ' + name + ' to authorize the connection.')) {
            return;
        }

        button.prop('disabled', true).text('Connecting...');

        $.post(kontentFireAjax.ajax_url, {
            action: 'kf_get_oauth_url',
            nonce: kontentFireAjax.nonce,
            platform: platform
        }, function(response) {
            if (response.success && response.data.oauth_url) {
                // Redirect to OAuth URL
                window.location.href = response.data.oauth_url;
            } else {
                alert('Error: ' + (response.data.message || 'Failed to get authorization URL'));
                button.prop('disabled', false).text('Connect ' + name);
            }
        }).fail(function() {
            alert('Connection failed. Please try again.');
            button.prop('disabled', false).text('Connect ' + name);
        });
    });

    // Disconnect platform
    $('.kf-disconnect-platform').on('click', function() {
        var platform = $(this).data('platform');
        var name = $(this).data('name');
        var button = $(this);

        if (!confirm('Disconnect ' + name + '?\n\nYou will need to reconnect to post to this platform.')) {
            return;
        }

        button.prop('disabled', true).text('Disconnecting...');

        $.post(kontentFireAjax.ajax_url, {
            action: 'kf_disconnect_platform',
            nonce: kontentFireAjax.nonce,
            platform: platform
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Failed to disconnect'));
                button.prop('disabled', false).text('Disconnect');
            }
        });
    });
});
</script>

<style>
.kf-platforms-page {
    max-width: 1200px;
}

.kf-platforms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.kf-platform-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s;
}

.kf-platform-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.kf-platform-card.connected {
    border-color: #46b450;
    background: #f0f9f0;
}

.platform-header {
    padding: 20px;
    background: #f9f9f9;
}

.platform-icon-large {
    font-size: 48px;
    text-align: center;
    margin-bottom: 10px;
}

.platform-header h3 {
    margin: 0;
    text-align: center;
    font-size: 20px;
}

.platform-body {
    padding: 20px;
}

.platform-description {
    color: #666;
    margin-bottom: 15px;
}

.account-type-badge {
    background: #E3F2FD;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
    margin-bottom: 15px;
}

.platform-features {
    font-size: 13px;
}

.platform-features ul {
    list-style: none;
    padding: 0;
    margin: 5px 0 0 0;
}

.platform-features li {
    padding: 3px 0;
    color: #46b450;
}

.platform-footer {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    text-align: center;
}

.platform-footer .button {
    width: 100%;
}

/* Connected Platforms */
.kf-connected-platforms {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.kf-connected-platform {
    display: flex;
    align-items: center;
    padding: 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.kf-connected-platform .platform-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-right: 15px;
}

.kf-connected-platform .platform-info {
    flex: 1;
}

.kf-connected-platform .platform-info h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.kf-connected-platform .account-name {
    font-weight: 600;
    color: #0073aa;
    margin: 0 0 3px 0;
}

.kf-connected-platform .connected-date {
    font-size: 12px;
    color: #666;
    margin: 0;
}

.kf-connected-platform .platform-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background: #E7F5E9;
    color: #46b450;
}
</style>
