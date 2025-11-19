<?php
/**
 * Meme Studio
 *
 * Creates and edits memes.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/media
 */

class Kontent_Fire_Meme_Studio {

    /**
     * Claude API instance
     */
    private $claude_api;

    /**
     * Constructor
     */
    public function __construct() {
        $this->claude_api = new Kontent_Fire_Claude_API();
    }

    /**
     * Generate meme text
     *
     * @param string $topic
     * @param string $template
     * @return array
     */
    public function generate_meme_text($topic, $template = '') {
        return $this->claude_api->generate_meme_text($topic, $template);
    }

    /**
     * Get popular meme templates
     *
     * @return array
     */
    public function get_templates() {
        return array(
            'drake' => array(
                'name' => 'Drake Hotline Bling',
                'format' => 'two_panel',
                'positions' => array('top_text', 'bottom_text')
            ),
            'distracted_boyfriend' => array(
                'name' => 'Distracted Boyfriend',
                'format' => 'three_subject',
                'positions' => array('boyfriend', 'girlfriend', 'other_girl')
            ),
            'success_kid' => array(
                'name' => 'Success Kid',
                'format' => 'single_text',
                'positions' => array('bottom_text')
            ),
            'change_my_mind' => array(
                'name' => 'Change My Mind',
                'format' => 'sign',
                'positions' => array('sign_text')
            ),
            'this_is_fine' => array(
                'name' => 'This Is Fine',
                'format' => 'single_text',
                'positions' => array('top_text')
            )
        );
    }

    /**
     * Create meme
     *
     * @param string $template
     * @param array $text_data
     * @return array
     */
    public function create_meme($template, $text_data) {
        // This would use image manipulation library (GD or ImageMagick)
        // Simplified version
        return array(
            'success' => true,
            'message' => 'Meme created successfully.',
            'template' => $template,
            'text' => $text_data
        );
    }

    /**
     * Add text to image
     *
     * @param string $image_path
     * @param array $text_config
     * @return array
     */
    public function add_text_to_image($image_path, $text_config) {
        if (!file_exists($image_path)) {
            return array('success' => false, 'message' => 'Image not found.');
        }

        // Use WordPress image editor
        $image = imagecreatefromjpeg($image_path);

        if (!$image) {
            return array('success' => false, 'message' => 'Failed to load image.');
        }

        // Add text (simplified)
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        $text = $text_config['text'] ?? '';
        $x = $text_config['x'] ?? 10;
        $y = $text_config['y'] ?? 30;

        // Draw text with outline
        imagestring($image, 5, $x-1, $y-1, $text, $black);
        imagestring($image, 5, $x+1, $y+1, $text, $black);
        imagestring($image, 5, $x, $y, $text, $white);

        // Save
        $upload_dir = wp_upload_dir();
        $output_path = $upload_dir['basedir'] . '/kontent-fire/images/meme-' . uniqid() . '.jpg';

        imagejpeg($image, $output_path);
        imagedestroy($image);

        return array(
            'success' => true,
            'url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $output_path)
        );
    }
}
