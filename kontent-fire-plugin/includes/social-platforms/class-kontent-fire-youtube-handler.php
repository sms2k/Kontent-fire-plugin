<?php
/**
 * YouTube Platform Handler
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/social-platforms
 */

class Kontent_Fire_YouTube_Handler {

    private $api_endpoint = 'https://www.googleapis.com/youtube/v3';

    public function post($params) {
        $connection = $this->get_connection($params['user_id']);
        if (!$connection) return array('success' => false, 'message' => 'YouTube not connected.');

        // YouTube video uploads require multipart upload - simplified version here
        return array('success' => false, 'message' => 'YouTube video upload requires additional implementation. Use YouTube Data API v3 for full support.');
    }

    public function connect($credentials) {
        global $wpdb;
        $wpdb->replace($wpdb->prefix . 'kf_platform_connections', array(
            'user_id' => get_current_user_id(),
            'platform' => 'youtube',
            'access_token' => $credentials['access_token'],
            'status' => 'active'
        ));
        return array('success' => true);
    }

    public function disconnect($user_id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'kf_platform_connections', array('user_id' => $user_id, 'platform' => 'youtube'));
    }

    public function get_status($user_id) {
        return array('connected' => !empty($this->get_connection($user_id)));
    }

    public function get_analytics($post_id, $user_id) {
        $connection = $this->get_connection($user_id);
        if (!$connection) return array('success' => false);

        $response = wp_remote_get($this->api_endpoint . "/videos?part=statistics&id={$post_id}&key=" . $connection['access_token']);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $stats = $data['items'][0]['statistics'] ?? array();

        return array(
            'success' => true,
            'views' => $stats['viewCount'] ?? 0,
            'likes' => $stats['likeCount'] ?? 0,
            'comments' => $stats['commentCount'] ?? 0
        );
    }

    private function get_connection($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kf_platform_connections WHERE user_id = %d AND platform = 'youtube' AND status = 'active'",
            $user_id
        ), ARRAY_A);
    }
}
