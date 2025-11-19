<?php
/**
 * Media Studio Page
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>🎨 Media Studio</h1>
    <p>Create AI-generated images and videos for your content using Imagen 4 and Veo 3.</p>

    <div class="kf-media-studio-container" style="margin-top: 30px;">
        <!-- Tab Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="#tab-images" class="nav-tab nav-tab-active">Images</a>
            <a href="#tab-videos" class="nav-tab">Videos</a>
            <a href="#tab-gallery" class="nav-tab">My Media</a>
        </h2>

        <!-- Image Generation Tab -->
        <div id="tab-images" class="kf-tab-content">
            <div class="kf-card">
                <h2>🖼️ Generate Images with Imagen 4</h2>
                <form id="kf-image-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="image-prompt">Image Description</label></th>
                            <td>
                                <textarea id="image-prompt" name="prompt" rows="4" class="large-text" required placeholder="Describe the image you want to create... Be detailed for best results!"></textarea>
                                <p class="description">Example: "Professional office space with modern design, bright natural lighting, plants and minimalist furniture"</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="image-style">Style</label></th>
                            <td>
                                <select id="image-style" name="style">
                                    <option value="">Default</option>
                                    <option value="photorealistic">Photorealistic</option>
                                    <option value="artistic">Artistic</option>
                                    <option value="illustration">Illustration</option>
                                    <option value="minimalist">Minimalist</option>
                                    <option value="professional">Professional/Corporate</option>
                                    <option value="modern">Modern Design</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="image-aspect">Aspect Ratio</label></th>
                            <td>
                                <select id="image-aspect" name="aspectRatio">
                                    <option value="16:9">16:9 (Landscape - Blog Featured)</option>
                                    <option value="1:1">1:1 (Square - Social Media)</option>
                                    <option value="9:16">9:16 (Portrait - Stories)</option>
                                    <option value="4:3">4:3 (Standard)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">✨ Generate Image</button>
                    </p>
                </form>

                <div id="image-result" style="display:none; margin-top: 30px;">
                    <h3>Generated Image</h3>
                    <div id="image-preview" style="text-align: center; padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;"></div>
                    <p class="submit">
                        <button id="save-to-media-library" class="button button-primary">Save to Media Library</button>
                        <button id="download-image" class="button">Download</button>
                        <button id="generate-another" class="button">Generate Another</button>
                    </p>
                </div>
            </div>
        </div>

        <!-- Video Generation Tab -->
        <div id="tab-videos" class="kf-tab-content" style="display:none;">
            <div class="kf-card">
                <h2>🎬 Generate Videos with Veo 3</h2>
                <form id="kf-video-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="video-topic">Video Topic</label></th>
                            <td>
                                <input type="text" id="video-topic" name="topic" class="large-text" required placeholder="What should the video be about?">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="video-duration">Duration</label></th>
                            <td>
                                <select id="video-duration" name="duration">
                                    <option value="15-30 seconds">15-30 seconds (Short)</option>
                                    <option value="30-60 seconds">30-60 seconds (Medium)</option>
                                    <option value="60-120 seconds">60-120 seconds (Long)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="video-platform">Platform</label></th>
                            <td>
                                <select id="video-platform" name="platform">
                                    <option value="youtube">YouTube</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="instagram">Instagram Reels</option>
                                    <option value="facebook">Facebook</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">🎥 Generate Video Script</button>
                    </p>
                </form>

                <div id="video-result" style="display:none; margin-top: 30px;">
                    <h3>Generated Video Script</h3>
                    <div id="video-script" class="kf-result-box"></div>
                    <p class="description">Video generation is a two-step process. First we generate the script, then you can generate the actual video.</p>
                    <p class="submit">
                        <button id="generate-video-file" class="button button-primary">Generate Video File</button>
                        <button id="edit-script" class="button">Edit Script</button>
                    </p>
                </div>
            </div>
        </div>

        <!-- Media Gallery Tab -->
        <div id="tab-gallery" class="kf-tab-content" style="display:none;">
            <div class="kf-card">
                <h2>📁 My Generated Media</h2>
                <p>View all your AI-generated images and videos.</p>
                <div id="media-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                    <p style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                        No media generated yet. Create some images or videos to see them here!
                    </p>
                </div>
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

    // Generate Image
    $('#kf-image-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'kf_generate_image',
            nonce: kontentFireAjax.nonce,
            prompt: $('#image-prompt').val(),
            style: $('#image-style').val(),
            aspectRatio: $('#image-aspect').val()
        };

        $('#image-result').hide();
        $(this).find('button[type="submit"]').prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 2s infinite linear;"></span> Generating...');

        $.post(kontentFireAjax.ajax_url, formData, function(response) {
            $('#kf-image-form button[type="submit"]').prop('disabled', false).html('✨ Generate Image');

            if (response.success && response.images && response.images.length > 0) {
                $('#image-preview').html('<img src="' + response.images[0] + '" style="max-width: 100%; height: auto; border-radius: 4px;">');
                $('#image-result').show();
                $('#download-image').data('url', response.images[0]);
            } else {
                alert('Error: ' + (response.message || 'Failed to generate image'));
            }
        }).fail(function() {
            $('#kf-image-form button[type="submit"]').prop('disabled', false).html('✨ Generate Image');
            alert('Error: Failed to connect to server');
        });
    });

    // Generate Video
    $('#kf-video-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'kf_generate_video',
            nonce: kontentFireAjax.nonce,
            topic: $('#video-topic').val(),
            duration: $('#video-duration').val(),
            platform: $('#video-platform').val()
        };

        $('#video-result').hide();
        $(this).find('button[type="submit"]').prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 2s infinite linear;"></span> Generating Script...');

        $.post(kontentFireAjax.ajax_url, formData, function(response) {
            $('#kf-video-form button[type="submit"]').prop('disabled', false).html('🎥 Generate Video Script');

            if (response.success) {
                $('#video-script').html('<pre>' + JSON.stringify(response.script, null, 2) + '</pre>');
                $('#video-result').show();
            } else {
                alert('Error: ' + (response.message || 'Failed to generate video script'));
            }
        }).fail(function() {
            $('#kf-video-form button[type="submit"]').prop('disabled', false).html('🎥 Generate Video Script');
            alert('Error: Failed to connect to server');
        });
    });

    // Download image
    $('#download-image').on('click', function() {
        var url = $(this).data('url');
        if (url) {
            window.open(url, '_blank');
        }
    });

    // Generate another
    $('#generate-another').on('click', function() {
        $('#image-result').hide();
        $('#image-prompt').val('').focus();
    });
});
</script>

<style>
@keyframes rotation {
    from { transform: rotate(0deg); }
    to { transform: rotate(359deg); }
}

.kf-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-top: 20px;
}

.kf-result-box {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    max-height: 400px;
    overflow-y: auto;
}
</style>
