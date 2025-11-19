<?php
/**
 * Content Generator page
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>Content Generator</h1>

    <div class="kf-generator-container">
        <div class="kf-card">
            <h2>Generate New Content</h2>

            <form id="kf-generate-form">
                <table class="form-table">
                    <tr>
                        <th><label for="content-type">Content Type</label></th>
                        <td>
                            <select id="content-type" name="type" required>
                                <option value="blog">Blog Post</option>
                                <option value="social">Social Media Post</option>
                                <option value="email">Email</option>
                                <option value="video_script">Video Script</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="topic">Topic</label></th>
                        <td>
                            <input type="text" id="topic" name="topic" class="regular-text" required placeholder="Enter your topic...">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="tone">Tone</label></th>
                        <td>
                            <select id="tone" name="tone">
                                <option value="professional">Professional</option>
                                <option value="casual">Casual</option>
                                <option value="friendly">Friendly</option>
                                <option value="humorous">Humorous</option>
                                <option value="formal">Formal</option>
                            </select>
                        </td>
                    </tr>

                    <tr class="social-platforms" style="display:none;">
                        <th><label>Platforms</label></th>
                        <td>
                            <label><input type="checkbox" name="platforms[]" value="facebook"> Facebook</label><br>
                            <label><input type="checkbox" name="platforms[]" value="instagram"> Instagram</label><br>
                            <label><input type="checkbox" name="platforms[]" value="twitter"> Twitter/X</label><br>
                            <label><input type="checkbox" name="platforms[]" value="linkedin"> LinkedIn</label><br>
                            <label><input type="checkbox" name="platforms[]" value="tiktok"> TikTok</label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="keywords">Keywords (optional)</label></th>
                        <td>
                            <input type="text" id="keywords" name="keywords" class="regular-text" placeholder="keyword1, keyword2, keyword3">
                            <p class="description">Comma-separated keywords for SEO</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">Generate Content</button>
                </p>
            </form>

            <div id="generation-result" style="display:none; margin-top: 30px;">
                <h3>Generated Content</h3>
                <div id="result-content" class="kf-result-box"></div>

                <p class="submit">
                    <button id="save-draft" class="button">Save as Draft</button>
                    <button id="schedule-post" class="button">Schedule Post</button>
                    <button id="post-now" class="button button-primary">Post Now</button>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#content-type').on('change', function() {
        if ($(this).val() === 'social') {
            $('.social-platforms').show();
        } else {
            $('.social-platforms').hide();
        }
    });

    $('#kf-generate-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'kf_generate_content',
            nonce: kontentFireAjax.nonce,
            type: $('#content-type').val(),
            topic: $('#topic').val(),
            tone: $('#tone').val(),
            platforms: $('input[name="platforms[]"]:checked').map(function() {
                return $(this).val();
            }).get()
        };

        $('#generation-result').hide();
        $(this).find('button[type="submit"]').prop('disabled', true).text('Generating...');

        $.post(kontentFireAjax.ajax_url, formData, function(response) {
            $('#kf-generate-form button[type="submit"]').prop('disabled', false).text('Generate Content');

            if (response.success) {
                $('#result-content').html('<pre>' + JSON.stringify(response.content, null, 2) + '</pre>');
                $('#generation-result').show();
            } else {
                alert('Error: ' + response.message);
            }
        });
    });
});
</script>

<style>
.kf-result-box {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    max-height: 400px;
    overflow-y: auto;
}
</style>
