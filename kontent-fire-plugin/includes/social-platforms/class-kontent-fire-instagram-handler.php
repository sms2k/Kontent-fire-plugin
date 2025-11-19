<?php
/**
 * Instagram Platform Handler
 *
 * Handles Instagram Graph API interactions.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/social-platforms
 */

class Kontent_Fire_Instagram_Handler {

    private $api_endpoint = 'https://graph.facebook.com/v18.0';

    public function post($params) {
        $connection = $this->get_connection($params['user_id']);

        if (!$connection) {
            return array('success' => false, 'message' => 'Instagram not connected.');
        }

        $instagram_account_id = $connection['platform_user_id'];
        $access_token = $connection['access_token'];

        // Instagram requires media for all posts
        if (empty($params['media_url'])) {
            return array('success' => false, 'message' => 'Instagram requires an image or video.');
        }

        // Step 1: Create media container
        $container_data = array(
            'image_url' => $params['media_url'],
            'caption' => $params['content'],
            'access_token' => $access_token
        );

        $container_response = wp_remote_post($this->api_endpoint . "/{$instagram_account_id}/media", array(
            'body' => $container_data
        ));

        $container_body = wp_remote_retrieve_body($container_response);
        $container = json_decode($container_body, true);

        if (!isset($container['id'])) {
            return array('success' => false, 'message' => 'Failed to create media container.');
        }

        // Step 2: Publish media
        $publish_data = array(
            'creation_id' => $container['id'],
            'access_token' => $access_token
        );

        $publish_response = wp_remote_post($this->api_endpoint . "/{$instagram_account_id}/media_publish", array(
            'body' => $publish_data
        ));

        $publish_body = wp_remote_retrieve_body($publish_response);
        $result = json_decode($publish_body, true);

        if (isset($result['id'])) {
            return array('success' => true, 'post_id' => $result['id']);
        }

        return array('success' => false, 'message' => 'Failed to publish to Instagram.');
    }

    public function connect($credentials) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        $wpdb->replace($table, array(
            'user_id' => get_current_user_id(),
            'platform' => 'instagram',
            'access_token' => $credentials['access_token'],
            'platform_user_id' => $credentials['instagram_account_id'],
            'status' => 'active'
        ));

        return array('success' => true);
    }

    public function disconnect($user_id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'kf_platform_connections', array('user_id' => $user_id, 'platform' => 'instagram'));
    }

    public function get_status($user_id) {
        $connection = $this->get_connection($user_id);
        return array('connected' => !empty($connection));
    }

    public function get_analytics($post_id, $user_id) {
        $connection = $this->get_connection($user_id);
        if (!$connection) return array('success' => false);

        $access_token = $connection['access_token'];
        $response = wp_remote_get($this->api_endpoint . "/{$post_id}?fields=like_count,comments_count&access_token={$access_token}");

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return array(
            'success' => true,
            'likes' => $data['like_count'] ?? 0,
            'comments' => $data['comments_count'] ?? 0
        );
    }

    private function get_connection($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kf_platform_connections WHERE user_id = %d AND platform = 'instagram' AND status = 'active'",
            $user_id
        ), ARRAY_A);
    }
}
