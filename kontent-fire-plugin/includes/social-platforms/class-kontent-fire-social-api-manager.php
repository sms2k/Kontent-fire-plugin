<?php
/**
 * Social API Manager
 *
 * Centralized manager for all social platform APIs.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/social-platforms
 */

class Kontent_Fire_Social_API_Manager {

    /**
     * Platform handlers
     */
    private $handlers = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_handlers();
    }

    /**
     * Load platform handlers
     */
    private function load_handlers() {
        $this->handlers = array(
            'facebook' => new Kontent_Fire_Facebook_Handler(),
            'instagram' => new Kontent_Fire_Instagram_Handler(),
            'twitter' => new Kontent_Fire_Twitter_Handler(),
            'linkedin' => new Kontent_Fire_LinkedIn_Handler(),
            'tiktok' => new Kontent_Fire_TikTok_Handler(),
            'youtube' => new Kontent_Fire_YouTube_Handler(),
        );
    }

    /**
     * Post content to a platform
     *
     * @param string $platform
     * @param array $params
     * @return array
     */
    public function post_content($platform, $params) {
        if (!isset($this->handlers[$platform])) {
            return array(
                'success' => false,
                'message' => "Platform '{$platform}' not supported."
            );
        }

        return $this->handlers[$platform]->post($params);
    }

    /**
     * Connect to a platform
     *
     * @param string $platform
     * @param array $credentials
     * @return array
     */
    public function connect($platform, $credentials) {
        if (!isset($this->handlers[$platform])) {
            return array(
                'success' => false,
                'message' => "Platform '{$platform}' not supported."
            );
        }

        return $this->handlers[$platform]->connect($credentials);
    }

    /**
     * Disconnect from a platform
     *
     * @param string $platform
     * @param int $user_id
     * @return bool
     */
    public function disconnect($platform, $user_id) {
        if (!isset($this->handlers[$platform])) {
            return false;
        }

        return $this->handlers[$platform]->disconnect($user_id);
    }

    /**
     * Get connection status
     *
     * @param string $platform
     * @param int $user_id
     * @return array
     */
    public function get_connection_status($platform, $user_id) {
        if (!isset($this->handlers[$platform])) {
            return array('connected' => false);
        }

        return $this->handlers[$platform]->get_status($user_id);
    }

    /**
     * Get analytics for a post
     *
     * @param string $platform
     * @param string $post_id
     * @param int $user_id
     * @return array
     */
    public function get_post_analytics($platform, $post_id, $user_id) {
        if (!isset($this->handlers[$platform])) {
            return array('success' => false);
        }

        return $this->handlers[$platform]->get_analytics($post_id, $user_id);
    }

    /**
     * Get available platforms
     *
     * @return array
     */
    public function get_available_platforms() {
        return array_keys($this->handlers);
    }
}
