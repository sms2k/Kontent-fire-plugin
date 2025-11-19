<?php
/**
 * Image Generator
 *
 * Generates images using AI APIs.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/media
 */

class Kontent_Fire_Image_Generator {

    /**
     * OpenAI API instance
     */
    private $openai_api;

    /**
     * Gemini API instance
     */
    private $gemini_api;

    /**
     * License manager
     */
    private $license_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->openai_api = new Kontent_Fire_OpenAI_API();
        $this->gemini_api = new Kontent_Fire_Gemini_API();
        $this->license_manager = new Kontent_Fire_License_Manager();
    }

    /**
     * Generate image
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate($prompt, $options = array()) {
        if (!$this->license_manager->has_feature('image_generation')) {
            return array(
                'success' => false,
                'message' => 'Image generation is not available in your plan.'
            );
        }

        $api = $options['api'] ?? 'openai';

        if ($api === 'openai') {
            return $this->generate_with_openai($prompt, $options);
        } elseif ($api === 'gemini') {
            return $this->generate_with_gemini($prompt, $options);
        }

        return array(
            'success' => false,
            'message' => 'Invalid API specified.'
        );
    }

    /**
     * Generate with OpenAI DALL-E
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    private function generate_with_openai($prompt, $options) {
        $defaults = array(
            'size' => '1024x1024',
            'quality' => 'standard',
            'n' => 1
        );

        $options = wp_parse_args($options, $defaults);

        return $this->openai_api->generate_image($prompt, $options);
    }

    /**
     * Generate with Gemini
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    private function generate_with_gemini($prompt, $options) {
        return $this->gemini_api->generate_image($prompt, $options);
    }

    /**
     * Generate social media images
     *
     * @param string $topic
     * @param array $platforms
     * @return array
     */
    public function generate_for_platforms($topic, $platforms) {
        $platform_sizes = array(
            'facebook' => '1200x630',
            'instagram' => '1080x1080',
            'twitter' => '1200x675',
            'linkedin' => '1200x627',
            'pinterest' => '1000x1500',
            'youtube' => '1280x720'
        );

        $results = array();

        foreach ($platforms as $platform) {
            $size = $platform_sizes[$platform] ?? '1024x1024';

            $prompt = "Create a professional, eye-catching social media image for {$platform} about: {$topic}. Make it visually appealing and optimized for social engagement.";

            $result = $this->generate($prompt, array(
                'size' => $size,
                'quality' => 'hd'
            ));

            $results[$platform] = $result;
        }

        return array(
            'success' => true,
            'results' => $results
        );
    }

    /**
     * Optimize image for platform
     *
     * @param string $image_url
     * @param string $platform
     * @return array
     */
    public function optimize_for_platform($image_url, $platform) {
        // Get platform-specific dimensions
        $dimensions = array(
            'facebook' => array('width' => 1200, 'height' => 630),
            'instagram' => array('width' => 1080, 'height' => 1080),
            'twitter' => array('width' => 1200, 'height' => 675),
            'linkedin' => array('width' => 1200, 'height' => 627)
        );

        $target = $dimensions[$platform] ?? array('width' => 1024, 'height' => 1024);

        // Download image
        $image_data = wp_remote_get($image_url);
        if (is_wp_error($image_data)) {
            return array('success' => false, 'message' => 'Failed to download image.');
        }

        $image_body = wp_remote_retrieve_body($image_data);

        // Save to temp file
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/kontent-fire/temp/' . uniqid() . '.jpg';

        file_put_contents($temp_file, $image_body);

        // Resize using WordPress image editor
        $image_editor = wp_get_image_editor($temp_file);

        if (is_wp_error($image_editor)) {
            return array('success' => false, 'message' => 'Failed to process image.');
        }

        $image_editor->resize($target['width'], $target['height'], true);

        $optimized_file = $upload_dir['basedir'] . '/kontent-fire/images/' . uniqid() . '.jpg';
        $image_editor->save($optimized_file);

        // Clean up temp file
        unlink($temp_file);

        return array(
            'success' => true,
            'optimized_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $optimized_file)
        );
    }

    /**
     * Batch generate images
     *
     * @param array $prompts
     * @param array $options
     * @return array
     */
    public function batch_generate($prompts, $options = array()) {
        $results = array();

        foreach ($prompts as $prompt) {
            $results[] = $this->generate($prompt, $options);

            // Rate limiting delay
            sleep(2);
        }

        return array(
            'success' => true,
            'results' => $results
        );
    }
}
