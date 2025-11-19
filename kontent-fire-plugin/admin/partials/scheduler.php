<?php
/**
 * Scheduler page
 */
if (!defined('WPINC')) {
    die;
}

// Get scheduled posts
global $wpdb;
$table = $wpdb->prefix . 'kf_posts';
$scheduled_posts = $wpdb->get_results(
    "SELECT * FROM $table WHERE status = 'scheduled' ORDER BY scheduled_time ASC",
    ARRAY_A
);

$completed_posts = $wpdb->get_results(
    "SELECT * FROM $table WHERE status = 'posted' ORDER BY posted_time DESC LIMIT 20",
    ARRAY_A
);
?>

<div class="wrap">
    <h1>📅 Schedule Posts</h1>
    <p>Schedule your content for automatic posting across multiple platforms.</p>

    <div class="kf-scheduler-container" style="margin-top: 30px;">
        <!-- Tab Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="#tab-schedule" class="nav-tab nav-tab-active">Schedule New</a>
            <a href="#tab-queued" class="nav-tab">Queued Posts (<?php echo count($scheduled_posts); ?>)</a>
            <a href="#tab-history" class="nav-tab">Post History</a>
        </h2>

        <!-- Schedule New Tab -->
        <div id="tab-schedule" class="kf-tab-content">
            <div class="kf-card">
                <h2>📝 Schedule New Post</h2>
                <form id="kf-schedule-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="platform">Platform</label></th>
                            <td>
                                <select id="platform" name="platform" required>
                                    <option value="">Select Platform</option>
                                    <option value="facebook">📘 Facebook</option>
                                    <option value="instagram">📷 Instagram</option>
                                    <option value="twitter">🐦 Twitter/X</option>
                                    <option value="linkedin">💼 LinkedIn</option>
                                    <option value="tiktok">🎵 TikTok</option>
                                    <option value="youtube">🎥 YouTube</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="content">Content</label></th>
                            <td>
                                <textarea id="content" name="content" rows="6" class="large-text" required placeholder="Enter your post content..."></textarea>
                                <p class="description" id="char-count">0 characters</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="media-url">Media URL (Optional)</label></th>
                            <td>
                                <input type="url" id="media-url" name="media_url" class="regular-text" placeholder="https://...">
                                <p class="description">URL to image or video</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="scheduled-date">Schedule Date & Time</label></th>
                            <td>
                                <input type="datetime-local" id="scheduled-date" name="scheduled_time" required>
                                <p class="description">When to post (your timezone)</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">📅 Schedule Post</button>
                        <button type="button" id="post-now" class="button">Post Immediately</button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Queued Posts Tab -->
        <div id="tab-queued" class="kf-tab-content" style="display:none;">
            <div class="kf-card">
                <h2>⏰ Scheduled Posts</h2>
                <?php if (empty($scheduled_posts)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">
                        No posts scheduled yet. Schedule your first post above!
                    </p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Platform</th>
                                <th>Content</th>
                                <th>Scheduled For</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduled_posts as $post): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($post['platform'])); ?></td>
                                    <td><?php echo esc_html(substr($post['content'], 0, 100)) . (strlen($post['content']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($post['scheduled_time'])); ?></td>
                                    <td>
                                        <button class="button button-small edit-post" data-id="<?php echo $post['id']; ?>">Edit</button>
                                        <button class="button button-small delete-post" data-id="<?php echo $post['id']; ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Tab -->
        <div id="tab-history" class="kf-tab-content" style="display:none;">
            <div class="kf-card">
                <h2>📊 Post History</h2>
                <?php if (empty($completed_posts)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">
                        No posts published yet. Your post history will appear here.
                    </p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Platform</th>
                                <th>Content</th>
                                <th>Posted</th>
                                <th>Platform Post ID</th>
                                <th>Engagement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_posts as $post): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($post['platform'])); ?></td>
                                    <td><?php echo esc_html(substr($post['content'], 0, 100)) . (strlen($post['content']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($post['posted_time'])); ?></td>
                                    <td><?php echo esc_html($post['platform_post_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $engagement = json_decode($post['engagement_data'], true);
                                        if ($engagement) {
                                            echo 'Likes: ' . ($engagement['likes'] ?? 0) . ', ';
                                            echo 'Comments: ' . ($engagement['comments'] ?? 0);
                                        } else {
                                            echo 'No data';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.kf-tab-content').hide();
        $($(this).attr('href')).show();
    });

    // Character counter
    $('#content').on('input', function() {
        var length = $(this).val().length;
        $('#char-count').text(length + ' characters');
    });

    // Schedule form submission
    $('#kf-schedule-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'kf_schedule_post',
            nonce: kontentFireAjax.nonce,
            platform: $('#platform').val(),
            content: $('#content').val(),
            media_url: $('#media-url').val(),
            scheduled_time: $('#scheduled-date').val()
        };

        $(this).find('button[type="submit"]').prop('disabled', true).text('Scheduling...');

        $.post(kontentFireAjax.ajax_url, formData, function(response) {
            $('#kf-schedule-form button[type="submit"]').prop('disabled', false).text('📅 Schedule Post');

            if (response.success) {
                alert('Post scheduled successfully!');
                $('#kf-schedule-form')[0].reset();
                location.reload(); // Reload to show new scheduled post
            } else {
                alert('Error: ' + (response.message || 'Failed to schedule post'));
            }
        }).fail(function() {
            $('#kf-schedule-form button[type="submit"]').prop('disabled', false).text('📅 Schedule Post');
            alert('Error: Failed to connect to server');
        });
    });

    // Post immediately
    $('#post-now').on('click', function() {
        if (!confirm('Post this content immediately?')) return;

        var formData = {
            action: 'kf_schedule_post',
            nonce: kontentFireAjax.nonce,
            platform: $('#platform').val(),
            content: $('#content').val(),
            media_url: $('#media-url').val(),
            scheduled_time: new Date().toISOString().slice(0, 16) // Now
        };

        $(this).prop('disabled', true).text('Posting...');

        $.post(kontentFireAjax.ajax_url, formData, function(response) {
            $('#post-now').prop('disabled', false).text('Post Immediately');

            if (response.success) {
                alert('Posted successfully!');
                $('#kf-schedule-form')[0].reset();
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to post'));
            }
        });
    });

    // Delete scheduled post
    $('.delete-post').on('click', function() {
        if (!confirm('Delete this scheduled post?')) return;

        var postId = $(this).data('id');
        var row = $(this).closest('tr');

        $.post(kontentFireAjax.ajax_url, {
            action: 'kf_delete_scheduled_post',
            nonce: kontentFireAjax.nonce,
            post_id: postId
        }, function(response) {
            if (response.success) {
                row.fadeOut(function() { $(this).remove(); });
            } else {
                alert('Error: Failed to delete post');
            }
        });
    });
});
</script>

<style>
.kf-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-top: 20px;
}
</style>
