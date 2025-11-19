<?php
/**
 * OpenAI API Integration
 *
 * Handles communication with OpenAI's API for GPT and DALL-E.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/api
 */

class Kontent_Fire_OpenAI_API {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://api.openai.com/v1';

    /**
     * API key
     */
    private $api_key;

    /**
     * Model version
     */
    private $model = 'gpt-4-turbo-preview';

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('kontent_fire_openai_api_key');
    }

    /**
     * Generate content using GPT
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate_content($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key not configured.'
            );
        }

        $defaults = array(
            'max_tokens' => 2048,
            'temperature' => 0.7,
            'model' => $this->model
        );

        $options = wp_parse_args($options, $defaults);

        $body = array(
            'model' => $options['model'],
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are an expert content creator and marketing specialist.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature']
        );

        $response = wp_remote_post($this->api_endpoint . '/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'API error: ' . ($data['error']['message'] ?? 'Unknown error'),
                'code' => $response_code
            );
        }

        return array(
            'success' => true,
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? array()
        );
    }

    /**
     * Generate image using DALL-E
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate_image($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key not configured.'
            );
        }

        $defaults = array(
            'model' => 'dall-e-3',
            'size' => '1024x1024',
            'quality' => 'standard',
            'n' => 1
        );

        $options = wp_parse_args($options, $defaults);

        $body = array(
            'model' => $options['model'],
            'prompt' => $prompt,
            'size' => $options['size'],
            'quality' => $options['quality'],
            'n' => $options['n']
        );

        $response = wp_remote_post($this->api_endpoint . '/images/generations', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 120
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'API error: ' . ($data['error']['message'] ?? 'Unknown error'),
                'code' => $response_code
            );
        }

        // Download and save the image
        $image_urls = array();
        foreach ($data['data'] as $image) {
            $url = $image['url'];
            $saved = $this->download_and_save_image($url);
            if ($saved) {
                $image_urls[] = $saved;
            }
        }

        return array(
            'success' => true,
            'images' => $image_urls,
            'data' => $data
        );
    }

    /**
     * Download and save image to WordPress media library
     *
     * @param string $url
     * @return string|false
     */
    private function download_and_save_image($url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name' => 'kontent-fire-' . time() . '.png',
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, 0);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        return wp_get_attachment_url($id);
    }

    /**
     * Generate content with function calling (structured output)
     *
     * @param string $prompt
     * @param array $functions
     * @return array
     */
    public function generate_structured_content($prompt, $functions) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key not configured.'
            );
        }

        $body = array(
            'model' => 'gpt-4-turbo-preview',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'functions' => $functions,
            'function_call' => 'auto'
        );

        $response = wp_remote_post($this->api_endpoint . '/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        return array(
            'success' => true,
            'data' => $data
        );
    }

    /**
     * Generate embeddings for semantic search
     *
     * @param string $text
     * @return array
     */
    public function generate_embeddings($text) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key not configured.'
            );
        }

        $body = array(
            'model' => 'text-embedding-ada-002',
            'input' => $text
        );

        $response = wp_remote_post($this->api_endpoint . '/embeddings', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        return array(
            'success' => true,
            'embedding' => $data['data'][0]['embedding'] ?? array()
        );
    }

    /**
     * Moderate content
     *
     * @param string $content
     * @return array
     */
    public function moderate_content($content) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key not configured.'
            );
        }

        $body = array(
            'input' => $content
        );

        $response = wp_remote_post($this->api_endpoint . '/moderations', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        return array(
            'success' => true,
            'results' => $data['results'][0] ?? array()
        );
    }
}
