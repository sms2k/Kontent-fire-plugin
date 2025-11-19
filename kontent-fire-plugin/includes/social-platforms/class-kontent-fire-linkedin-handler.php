<?php
/**
 * LinkedIn Platform Handler
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/social-platforms
 */

class Kontent_Fire_LinkedIn_Handler {

    private $api_endpoint = 'https://api.linkedin.com/v2';

    public function post($params) {
        $connection = $this->get_connection($params['user_id']);
        if (!$connection) return array('success' => false, 'message' => 'LinkedIn not connected.');

        $post_data = array(
            'author' => 'urn:li:person:' . $connection['platform_user_id'],
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => array(
                'com.linkedin.ugc.ShareContent' => array(
                    'shareCommentary' => array('text' => $params['content']),
                    'shareMediaCategory' => 'NONE'
                )
            ),
            'visibility' => array('com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC')
        );

        $response = wp_remote_post($this->api_endpoint . '/ugcPosts', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $connection['access_token'],
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ),
            'body' => json_encode($post_data)
        ));

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['id'])) {
            return array('success' => true, 'post_id' => $body['id']);
        }

        return array('success' => false, 'message' => 'Failed to post to LinkedIn.');
    }

    public function connect($credentials) {
        global $wpdb;
        $wpdb->replace($wpdb->prefix . 'kf_platform_connections', array(
            'user_id' => get_current_user_id(),
            'platform' => 'linkedin',
            'access_token' => $credentials['access_token'],
            'platform_user_id' => $credentials['person_id'],
            'status' => 'active'
        ));
        return array('success' => true);
    }

    public function disconnect($user_id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'kf_platform_connections', array('user_id' => $user_id, 'platform' => 'linkedin'));
    }

    public function get_status($user_id) {
        return array('connected' => !empty($this->get_connection($user_id)));
    }

    public function get_analytics($post_id, $user_id) {
        return array('success' => true, 'likes' => 0, 'comments' => 0, 'shares' => 0);
    }

    private function get_connection($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kf_platform_connections WHERE user_id = %d AND platform = 'linkedin' AND status = 'active'",
            $user_id
        ), ARRAY_A);
    }
}
