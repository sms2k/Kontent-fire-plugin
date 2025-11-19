<?php
/**
 * Dashboard page
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap kontent-fire-dashboard">
    <h1>Kontent Fire Dashboard</h1>

    <?php if (!$is_active): ?>
    <div class="notice notice-warning">
        <p><strong>License Not Active:</strong> Please activate your license key in the <a href="<?php echo admin_url('admin.php?page=kontent-fire-settings'); ?>">Settings</a> to use all features.</p>
    </div>
    <?php endif; ?>

    <div class="kf-dashboard-grid">
        <!-- Quick Stats -->
        <div class="kf-card">
            <h2>Queue Status</h2>
            <div class="kf-stats">
                <div class="kf-stat">
                    <span class="kf-stat-value"><?php echo $queue_status['scheduled']; ?></span>
                    <span class="kf-stat-label">Scheduled</span>
                </div>
                <div class="kf-stat">
                    <span class="kf-stat-value"><?php echo $queue_status['queued']; ?></span>
                    <span class="kf-stat-label">Queued</span>
                </div>
                <div class="kf-stat">
                    <span class="kf-stat-value"><?php echo $queue_status['published']; ?></span>
                    <span class="kf-stat-label">Published</span>
                </div>
                <div class="kf-stat">
                    <span class="kf-stat-value"><?php echo $queue_status['failed']; ?></span>
                    <span class="kf-stat-label">Failed</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="kf-card">
            <h2>Quick Actions</h2>
            <div class="kf-actions">
                <a href="<?php echo admin_url('admin.php?page=kontent-fire-generate'); ?>" class="button button-primary button-large">
                    Generate Content
                </a>
                <a href="<?php echo admin_url('admin.php?page=kontent-fire-schedule'); ?>" class="button button-secondary button-large">
                    Schedule Post
                </a>
                <a href="<?php echo admin_url('admin.php?page=kontent-fire-media'); ?>" class="button button-secondary button-large">
                    Create Media
                </a>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="kf-card kf-full-width">
            <h2>Recent Posts</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $db = new Kontent_Fire_DB();
                    $posts = $db->get_user_posts(get_current_user_id(), array('limit' => 10));

                    if (empty($posts)):
                    ?>
                    <tr>
                        <td colspan="5">No posts yet. <a href="<?php echo admin_url('admin.php?page=kontent-fire-generate'); ?>">Create your first post!</a></td>
                    </tr>
                    <?php else:
                        foreach ($posts as $post):
                    ?>
                    <tr>
                        <td><?php echo esc_html(ucfirst($post['platform'])); ?></td>
                        <td><?php echo esc_html(substr(strip_tags($post['content']), 0, 100)) . '...'; ?></td>
                        <td><span class="kf-status-<?php echo esc_attr($post['status']); ?>"><?php echo esc_html(ucfirst($post['status'])); ?></span></td>
                        <td><?php echo esc_html($post['scheduled_time'] ?? 'Not scheduled'); ?></td>
                        <td>
                            <button class="button button-small">Edit</button>
                            <button class="button button-small">Delete</button>
                        </td>
                    </tr>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.kf-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 20px;
}

.kf-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.kf-full-width {
    grid-column: 1 / -1;
}

.kf-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.kf-stat {
    text-align: center;
}

.kf-stat-value {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}

.kf-stat-label {
    display: block;
    font-size: 13px;
    color: #646970;
}

.kf-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.kf-status-published { color: #00a32a; }
.kf-status-scheduled { color: #2271b1; }
.kf-status-failed { color: #d63638; }
.kf-status-draft { color: #dba617; }
</style>
