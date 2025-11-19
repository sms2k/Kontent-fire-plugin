<?php
/**
 * API Proxy - Routes all AI API calls through licensing server
 *
 * This ensures API keys are kept secret and credits are properly tracked
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/api
 */

class Kontent_Fire_API_Proxy {

    /**
     * License server API endpoint
     */
    private $proxy_url = 'https://your-license-server.com/api/proxy/';

    /**
     * Credit manager instance
     */
    private $credit_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->credit_manager = new Kontent_Fire_Credit_Manager();
    }

    /**
     * Call Claude API
     *
     * @param string $endpoint API endpoint (generate, blog, etc.)
     * @param array $params Parameters
     * @return array Result
     */
    public function call_claude($endpoint, $params) {
        // Check credits first
        if (!$this->credit_manager->has_credits('claude_content')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits. Please upgrade your plan.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('claude', $endpoint, $params);

        // Deduct credits if successful
        if ($result['success']) {
            $this->credit_manager->deduct_credits('claude_content', 1, array(
                'endpoint' => $endpoint,
                'model' => $params['model'] ?? 'claude-sonnet'
            ));
        }

        return $result;
    }

    /**
     * Call OpenAI API
     *
     * @param string $endpoint API endpoint
     * @param array $params Parameters
     * @return array Result
     */
    public function call_openai($endpoint, $params) {
        // Check credits
        if (!$this->credit_manager->has_credits('openai_content')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits. Please upgrade your plan.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('openai', $endpoint, $params);

        if ($result['success']) {
            $this->credit_manager->deduct_credits('openai_content', 1, array(
                'endpoint' => $endpoint,
                'model' => $params['model'] ?? 'gpt-4'
            ));
        }

        return $result;
    }

    /**
     * Call Gemini API
     *
     * @param string $endpoint API endpoint
     * @param array $params Parameters
     * @return array Result
     */
    public function call_gemini($endpoint, $params) {
        // Check credits
        if (!$this->credit_manager->has_credits('gemini_content')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits. Please upgrade your plan.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('gemini', $endpoint, $params);

        if ($result['success']) {
            $this->credit_manager->deduct_credits('gemini_content', 1, array(
                'endpoint' => $endpoint,
                'model' => $params['model'] ?? 'gemini-pro'
            ));
        }

        return $result;
    }

    /**
     * Generate image with Imagen 4
     *
     * @param string $prompt Image prompt
     * @param array $options Options
     * @return array Result with image URL
     */
    public function generate_image_imagen($prompt, $options = array()) {
        // Check credits
        if (!$this->credit_manager->has_credits('imagen_4')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits for image generation.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('imagen', 'generate', array_merge(
            array('prompt' => $prompt),
            $options
        ));

        if ($result['success']) {
            $this->credit_manager->deduct_credits('imagen_4', 1, array(
                'prompt' => substr($prompt, 0, 100)
            ));
        }

        return $result;
    }

    /**
     * Generate image with DALL-E 3
     *
     * @param string $prompt Image prompt
     * @param array $options Options
     * @return array Result with image URL
     */
    public function generate_image_dalle($prompt, $options = array()) {
        if (!$this->credit_manager->has_credits('dalle_3')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits for image generation.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('dalle', 'generate', array_merge(
            array('prompt' => $prompt),
            $options
        ));

        if ($result['success']) {
            $this->credit_manager->deduct_credits('dalle_3', 1, array(
                'prompt' => substr($prompt, 0, 100)
            ));
        }

        return $result;
    }

    /**
     * Generate video with Veo 3
     *
     * @param string $prompt Video prompt
     * @param array $options Options
     * @return array Result with video URL
     */
    public function generate_video_veo3($prompt, $options = array()) {
        if (!$this->credit_manager->has_credits('veo_3')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits for video generation.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('veo3', 'generate', array_merge(
            array('prompt' => $prompt),
            $options
        ));

        if ($result['success']) {
            $this->credit_manager->deduct_credits('veo_3', 1, array(
                'prompt' => substr($prompt, 0, 100),
                'duration' => $options['duration'] ?? '5'
            ));
        }

        return $result;
    }

    /**
     * Use local AI (Gemma/Llama) for structure
     *
     * @param string $prompt Prompt
     * @param array $options Options
     * @return array Result
     */
    public function call_local_ai($prompt, $options = array()) {
        // Local AI is very cheap, still deduct minimal credits
        $result = $this->proxy_request('local-ai', 'generate', array_merge(
            array('prompt' => $prompt),
            $options
        ));

        if ($result['success']) {
            $this->credit_manager->deduct_credits('local_ai', 1, array(
                'model' => $options['model'] ?? 'gemma-27b'
            ));
        }

        return $result;
    }

    /**
     * Post to social media
     *
     * @param string $platform Platform name
     * @param string $content Post content
     * @param array $options Options (images, etc.)
     * @return array Result
     */
    public function post_to_social($platform, $content, $options = array()) {
        if (!$this->credit_manager->has_credits('social_post')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits for social posting.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('social', 'post', array(
            'platform' => $platform,
            'content' => $content,
            'options' => $options
        ));

        if ($result['success']) {
            $this->credit_manager->deduct_credits('social_post', 1, array(
                'platform' => $platform
            ));
        }

        return $result;
    }

    /**
     * Perform keyword research
     *
     * @param string $topic Topic
     * @param array $options Options
     * @return array Result with keywords
     */
    public function keyword_research($topic, $options = array()) {
        if (!$this->credit_manager->has_credits('keyword_research')) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits for keyword research.',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        $result = $this->proxy_request('seo', 'keyword-research', array_merge(
            array('topic' => $topic),
            $options
        ));

        if ($result['success']) {
            $this->credit_manager->deduct_credits('keyword_research', 1, array(
                'topic' => $topic
            ));
        }

        return $result;
    }

    /**
     * Make proxy request to licensing server
     *
     * @param string $service Service name (claude, openai, etc.)
     * @param string $endpoint Endpoint
     * @param array $params Parameters
     * @return array Result
     */
    private function proxy_request($service, $endpoint, $params) {
        $license_key = get_option('kontent_fire_license_key');

        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => 'No license key configured. Please activate your license.'
            );
        }

        $request_data = array(
            'license_key' => $license_key,
            'site_url' => get_site_url(),
            'service' => $service,
            'endpoint' => $endpoint,
            'params' => json_encode($params),
            'timestamp' => time()
        );

        // Add signature for security
        $request_data['signature'] = $this->generate_signature($request_data);

        $response = wp_remote_post($this->proxy_url . $service . '/' . $endpoint, array(
            'body' => $request_data,
            'timeout' => 60, // AI operations can take time
            'headers' => array(
                'User-Agent' => 'Kontent-Fire/' . KONTENT_FIRE_VERSION
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Handle specific error codes
        if ($http_code === 402) {
            return array(
                'success' => false,
                'message' => 'Insufficient credits',
                'code' => 'insufficient_credits',
                'upgrade_url' => $this->credit_manager->get_upgrade_url()
            );
        }

        if ($http_code === 401) {
            return array(
                'success' => false,
                'message' => 'Invalid or expired license',
                'code' => 'invalid_license'
            );
        }

        if ($http_code !== 200) {
            return array(
                'success' => false,
                'message' => $data['message'] ?? 'API request failed'
            );
        }

        return $data;
    }

    /**
     * Generate request signature
     *
     * @param array $data Request data
     * @return string Signature
     */
    private function generate_signature($data) {
        $string_to_sign = $data['license_key'] . '|' .
                         $data['site_url'] . '|' .
                         $data['timestamp'];

        return hash_hmac('sha256', $string_to_sign, KONTENT_FIRE_VERSION);
    }

    /**
     * Test API connection
     *
     * @return array Test result
     */
    public function test_connection() {
        $result = $this->proxy_request('health', 'check', array());

        return array(
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Connection test failed',
            'credits' => $this->credit_manager->get_balance()
        );
    }

    /**
     * Get API status
     *
     * @return array Status of all APIs
     */
    public function get_api_status() {
        $result = $this->proxy_request('health', 'status', array());

        if ($result['success']) {
            return $result['status'];
        }

        return array(
            'claude' => 'unknown',
            'openai' => 'unknown',
            'gemini' => 'unknown'
        );
    }
}
