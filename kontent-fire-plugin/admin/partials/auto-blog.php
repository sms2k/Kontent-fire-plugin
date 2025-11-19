<?php
/**
 * Auto-Blog Generator page
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>🔥 Automatic Blog Generator</h1>
    <p class="description">Let AI create SEO-optimized blogs automatically with deep research, LSI keywords, and local targeting.</p>

    <div class="kf-auto-blog-container">
        <!-- Settings Section -->
        <div class="kf-card">
            <h2>Auto-Blogging Settings</h2>

            <form method="post" action="options.php" id="kf-auto-blog-settings">
                <?php settings_fields('kontent_fire_auto_blog'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kontent_fire_auto_blog_enabled">Enable Auto-Blogging</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       id="kontent_fire_auto_blog_enabled"
                                       name="kontent_fire_auto_blog_enabled"
                                       value="yes"
                                       <?php checked(get_option('kontent_fire_auto_blog_enabled'), 'yes'); ?>>
                                Automatically generate and publish SEO-optimized blogs
                            </label>
                            <p class="description">When enabled, AI will create high-quality blogs based on your settings below.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kontent_fire_business_info">Business/Company</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="kontent_fire_business_info"
                                   name="kontent_fire_business_info"
                                   value="<?php echo esc_attr(get_option('kontent_fire_business_info')); ?>"
                                   class="regular-text"
                                   required
                                   placeholder="e.g., Acme Web Design">
                            <p class="description">Your business name or description</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kontent_fire_industry">Industry</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="kontent_fire_industry"
                                   name="kontent_fire_industry"
                                   value="<?php echo esc_attr(get_option('kontent_fire_industry')); ?>"
                                   class="regular-text"
                                   required
                                   placeholder="e.g., Web Development, Real Estate, Legal Services">
                            <p class="description">Your industry or niche</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kontent_fire_target_zip_codes">Target ZIP Codes (Local SEO)</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="kontent_fire_target_zip_codes"
                                   name="kontent_fire_target_zip_codes"
                                   value="<?php echo esc_attr(get_option('kontent_fire_target_zip_codes')); ?>"
                                   class="regular-text"
                                   placeholder="e.g., 90210, 10001, 60601">
                            <p class="description">
                                <strong>For LOCAL SEO:</strong> Enter zip codes (comma-separated). AI will create hyper-local content mentioning neighborhoods, business areas, and landmarks.<br>
                                <strong>For NATIONAL SEO:</strong> Leave blank to focus on industry-level thought leadership.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kontent_fire_auto_blog_frequency">Generation Frequency</label>
                        </th>
                        <td>
                            <select id="kontent_fire_auto_blog_frequency" name="kontent_fire_auto_blog_frequency">
                                <option value="daily" <?php selected(get_option('kontent_fire_auto_blog_frequency'), 'daily'); ?>>Daily (1 blog/day)</option>
                                <option value="twice_weekly" <?php selected(get_option('kontent_fire_auto_blog_frequency'), 'twice_weekly'); ?>>Twice Weekly (2 blogs/week)</option>
                                <option value="weekly" <?php selected(get_option('kontent_fire_auto_blog_frequency'), 'weekly'); ?>>Weekly (1 blog/week)</option>
                                <option value="monthly" <?php selected(get_option('kontent_fire_auto_blog_frequency'), 'monthly'); ?>>Monthly (1 blog/month)</option>
                            </select>
                            <p class="description">How often to automatically generate new blogs</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kontent_fire_auto_blog_status">Blog Status</label>
                        </th>
                        <td>
                            <select id="kontent_fire_auto_blog_status" name="kontent_fire_auto_blog_status">
                                <option value="publish" <?php selected(get_option('kontent_fire_auto_blog_status'), 'publish'); ?>>Publish Immediately</option>
                                <option value="draft" <?php selected(get_option('kontent_fire_auto_blog_status'), 'draft'); ?>>Save as Draft</option>
                            </select>
                            <p class="description">Whether to publish automatically or save for review</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="kontent_fire_blog_images_per_post">Images Per Blog</label>
                        </th>
                        <td>
                            <input type="number"
                                   id="kontent_fire_blog_images_per_post"
                                   name="kontent_fire_blog_images_per_post"
                                   value="<?php echo esc_attr(get_option('kontent_fire_blog_images_per_post', '3')); ?>"
                                   min="1"
                                   max="5">
                            <p class="description">Number of Imagen 4 images to generate per blog (1-5)</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>

        <!-- Manual Generation Section -->
        <div class="kf-card" style="margin-top: 20px;">
            <h2>Generate Blog Now (Manual)</h2>
            <p>Generate a blog immediately without waiting for the schedule.</p>

            <form id="kf-manual-blog-form">
                <table class="form-table">
                    <tr>
                        <th><label for="manual-topic">Specific Topic (Optional)</label></th>
                        <td>
                            <input type="text"
                                   id="manual-topic"
                                   name="topic"
                                   class="regular-text"
                                   placeholder="Leave blank for AI to research and choose">
                            <p class="description">If blank, AI will research and select the best topic based on your settings</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large" id="generate-now-btn">
                        🔥 Generate Blog Now
                    </button>
                    <span id="generation-status" style="margin-left: 10px;"></span>
                </p>
            </form>

            <div id="generation-result" style="display:none; margin-top: 20px;">
                <h3>✅ Blog Generated Successfully!</h3>
                <div id="result-details"></div>
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="kf-card" style="margin-top: 20px; background: #f0f9ff; border-left: 4px solid #0ea5e9;">
            <h2>🤖 How Auto-Blogging Works</h2>

            <h3>The AI Process (Fully Automated):</h3>
            <ol style="line-height: 2;">
                <li><strong>Topic Research</strong> - AI analyzes your business/industry and finds trending, high-value topics</li>
                <li><strong>LSI Keyword Research</strong> - Deep keyword analysis with 15-20 LSI keywords, long-tail phrases, and related entities</li>
                <li><strong>Local Research</strong> (if zip codes provided) - Finds neighborhoods, business parks, shopping centers, landmarks</li>
                <li><strong>Content Creation</strong> - Claude writes 1500-2000 word SEO-optimized blog with:
                    <ul>
                        <li>Natural keyword integration (no stuffing)</li>
                        <li>Local mentions (if applicable)</li>
                        <li>H2/H3 subheadings with keywords</li>
                        <li>Expert insights and data</li>
                        <li>Strong call-to-action</li>
                    </ul>
                </li>
                <li><strong>Image Generation</strong> - Creates 2-3 professional images using Imagen 4</li>
                <li><strong>SEO Optimization</strong> - Adds meta descriptions, focus keywords, tags</li>
                <li><strong>Auto-Promotion</strong> - Creates and schedules social media posts (if enabled)</li>
            </ol>

            <h3>💡 Examples:</h3>
            <p><strong>Local SEO Example (ZIP: 90210):</strong></p>
            <blockquote>
                Blog topic: "Web Design Trends for Beverly Hills Businesses"<br>
                - Mentions: Rodeo Drive, Beverly Hills Business Triangle, local shopping centers<br>
                - Keywords: "Beverly Hills web design", "local website design 90210"<br>
                - Result: Ranks for local searches + drives local traffic
            </blockquote>

            <p><strong>National SEO Example (No ZIP):</strong></p>
            <blockquote>
                Blog topic: "The Future of AI in Web Development"<br>
                - Keywords: "AI web development", "machine learning websites"<br>
                - Focus: Thought leadership, industry insights<br>
                - Result: Ranks nationally + establishes authority
            </blockquote>

            <h3>✨ What You Get:</h3>
            <ul style="line-height: 2;">
                <li>✅ 1500-2000 word SEO-optimized blog</li>
                <li>✅ 2-3 professional Imagen 4 images</li>
                <li>✅ Perfect keyword density and placement</li>
                <li>✅ Local targeting (if zip codes provided)</li>
                <li>✅ Auto-scheduled social media promotion</li>
                <li>✅ Complete automation - zero manual work</li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#kf-manual-blog-form').on('submit', function(e) {
        e.preventDefault();

        var topic = $('#manual-topic').val();
        var button = $('#generate-now-btn');
        var status = $('#generation-status');
        var result = $('#generation-result');

        button.prop('disabled', true).text('🔄 Generating... (This may take 2-3 minutes)');
        status.html('<span style="color: #0ea5e9;">AI is researching, writing, and creating images...</span>');
        result.hide();

        $.post(kontentFireAjax.ajax_url, {
            action: 'kf_generate_auto_blog',
            nonce: kontentFireAjax.nonce,
            topic: topic
        }, function(response) {
            button.prop('disabled', false).text('🔥 Generate Blog Now');

            if (response.success) {
                status.html('<span style="color: #10b981;">✓ Complete!</span>');

                var details = '<div class="kf-result-box">';
                details += '<p><strong>Title:</strong> ' + response.data.title + '</p>';
                details += '<p><strong>Topic:</strong> ' + response.data.topic + '</p>';
                details += '<p><strong>Keywords:</strong> ' + response.data.keywords.join(', ') + '</p>';
                details += '<p><strong>Images:</strong> ' + response.data.images_count + ' generated</p>';
                details += '<p><strong>Targeting:</strong> ' + (response.data.local_targeting ? 'Local SEO' : 'National SEO') + '</p>';
                details += '<p><a href="' + '<?php echo admin_url('post.php?action=edit&post='); ?>' + response.data.post_id + '" class="button button-primary">View/Edit Post</a></p>';
                details += '</div>';

                $('#result-details').html(details);
                result.show();
            } else {
                status.html('<span style="color: #ef4444;">✗ Error: ' + response.message + '</span>');
            }
        }).fail(function() {
            button.prop('disabled', false).text('🔥 Generate Blog Now');
            status.html('<span style="color: #ef4444;">✗ Request failed. Please try again.</span>');
        });
    });
});
</script>

<style>
.kf-auto-blog-container {
    max-width: 900px;
}

.kf-result-box {
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    padding: 15px;
    border-radius: 4px;
}

.kf-result-box p {
    margin: 10px 0;
}

.kf-card h3 {
    margin-top: 20px;
}

.kf-card blockquote {
    background: #fff;
    padding: 15px;
    border-left: 4px solid #0ea5e9;
    margin: 10px 0;
}
</style>
