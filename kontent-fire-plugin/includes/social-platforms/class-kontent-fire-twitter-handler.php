<?php
/**
 * Twitter/X Platform Handler
 *
 * Handles Twitter API v2 interactions.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/social-platforms
 */

class Kontent_Fire_Twitter_Handler {

    private $api_endpoint = 'https://api.twitter.com/2';

    public function post($params) {
        $connection = $this->get_connection($params['user_id']);

        if (!$connection) {
            return array('success' => false, 'message' => 'Twitter not connected.');
        }

        $tweet_data = array('text' => $params['content']);

        // Add media if provided
        if (!empty($params['media_url'])) {
            // Note: Twitter requires uploading media separately via media upload endpoint
            // This is a simplified version
            $tweet_data['media'] = array('media_ids' => array());
        }

        $response = wp_remote_post($this->api_endpoint . '/tweets', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $connection['access_token'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($tweet_data)
        ));

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['data']['id'])) {
            return array('success' => true, 'post_id' => $body['data']['id']);
        }

        return array('success' => false, 'message' => 'Failed to post tweet.');
    }

    public function connect($credentials) {
        global $wpdb;
        $wpdb->replace($wpdb->prefix . 'kf_platform_connections', array(
            'user_id' => get_current_user_id(),
            'platform' => 'twitter',
            'access_token' => $credentials['access_token'],
            'status' => 'active'
        ));

        return array('success' => true);
    }

    public function disconnect($user_id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'kf_platform_connections', array('user_id' => $user_id, 'platform' => 'twitter'));
    }

    public function get_status($user_id) {
        return array('connected' => !empty($this->get_connection($user_id)));
    }

    public function get_analytics($post_id, $user_id) {
        $connection = $this->get_connection($user_id);
        if (!$connection) return array('success' => false);

        $response = wp_remote_get($this->api_endpoint . "/tweets/{$post_id}?tweet.fields=public_metrics", array(
            'headers' => array('Authorization' => 'Bearer ' . $connection['access_token'])
        ));

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $metrics = $data['data']['public_metrics'] ?? array();

        return array(
            'success' => true,
            'retweets' => $metrics['retweet_count'] ?? 0,
            'likes' => $metrics['like_count'] ?? 0,
            'replies' => $metrics['reply_count'] ?? 0
        );
    }

    private function get_connection($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kf_platform_connections WHERE user_id = %d AND platform = 'twitter' AND status = 'active'",
            $user_id
        ), ARRAY_A);
    }
}
