<?php
/**
 * Settings page
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>Kontent Fire Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('kontent_fire_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kontent_fire_license_key">License Key</label>
                </th>
                <td>
                    <input type="text"
                           id="kontent_fire_license_key"
                           name="kontent_fire_license_key"
                           value="<?php echo esc_attr(get_option('kontent_fire_license_key')); ?>"
                           class="regular-text">
                    <p class="description">Enter your Kontent Fire license key</p>
                    <button type="button" id="validate-license" class="button">Validate License</button>
                    <span id="license-status"></span>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="kontent_fire_claude_api_key">Claude API Key</label>
                </th>
                <td>
                    <input type="password"
                           id="kontent_fire_claude_api_key"
                           name="kontent_fire_claude_api_key"
                           value="<?php echo esc_attr(get_option('kontent_fire_claude_api_key')); ?>"
                           class="regular-text">
                    <p class="description">Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="kontent_fire_openai_api_key">OpenAI API Key</label>
                </th>
                <td>
                    <input type="password"
                           id="kontent_fire_openai_api_key"
                           name="kontent_fire_openai_api_key"
                           value="<?php echo esc_attr(get_option('kontent_fire_openai_api_key')); ?>"
                           class="regular-text">
                    <p class="description">Get your API key from <a href="https://platform.openai.com/" target="_blank">OpenAI Platform</a></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="kontent_fire_gemini_api_key">Google Gemini API Key</label>
                </th>
                <td>
                    <input type="password"
                           id="kontent_fire_gemini_api_key"
                           name="kontent_fire_gemini_api_key"
                           value="<?php echo esc_attr(get_option('kontent_fire_gemini_api_key')); ?>"
                           class="regular-text">
                    <p class="description">Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="kontent_fire_default_tone">Default Tone</label>
                </th>
                <td>
                    <select id="kontent_fire_default_tone" name="kontent_fire_default_tone">
                        <option value="professional" <?php selected(get_option('kontent_fire_default_tone'), 'professional'); ?>>Professional</option>
                        <option value="casual" <?php selected(get_option('kontent_fire_default_tone'), 'casual'); ?>>Casual</option>
                        <option value="friendly" <?php selected(get_option('kontent_fire_default_tone'), 'friendly'); ?>>Friendly</option>
                        <option value="formal" <?php selected(get_option('kontent_fire_default_tone'), 'formal'); ?>>Formal</option>
                        <option value="humorous" <?php selected(get_option('kontent_fire_default_tone'), 'humorous'); ?>>Humorous</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="kontent_fire_auto_post">Auto Post</label>
                </th>
                <td>
                    <input type="checkbox"
                           id="kontent_fire_auto_post"
                           name="kontent_fire_auto_post"
                           value="yes"
                           <?php checked(get_option('kontent_fire_auto_post'), 'yes'); ?>>
                    <label for="kontent_fire_auto_post">Automatically post scheduled content</label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#validate-license').on('click', function() {
        var licenseKey = $('#kontent_fire_license_key').val();
        var button = $(this);
        var status = $('#license-status');

        button.prop('disabled', true).text('Validating...');

        $.post(kontentFireAjax.ajax_url, {
            action: 'kf_validate_license',
            nonce: kontentFireAjax.nonce,
            license_key: licenseKey
        }, function(response) {
            button.prop('disabled', false).text('Validate License');

            if (response.success) {
                status.html('<span style="color: green;">✓ License activated!</span>');
            } else {
                status.html('<span style="color: red;">✗ ' + response.message + '</span>');
            }
        });
    });
});
</script>
