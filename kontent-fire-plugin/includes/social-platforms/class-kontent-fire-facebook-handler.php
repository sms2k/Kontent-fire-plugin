<?php
/**
 * Facebook Platform Handler
 *
 * Handles Facebook API interactions.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/social-platforms
 */

class Kontent_Fire_Facebook_Handler {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://graph.facebook.com/v18.0';

    /**
     * Post to Facebook
     *
     * @param array $params
     * @return array
     */
    public function post($params) {
        $connection = $this->get_connection($params['user_id']);

        if (!$connection) {
            return array(
                'success' => false,
                'message' => 'Facebook not connected.'
            );
        }

        $access_token = $connection['access_token'];
        $page_id = $connection['platform_user_id'];

        $post_data = array(
            'message' => $params['content'],
            'access_token' => $access_token
        );

        // Add media if provided
        if (!empty($params['media_url'])) {
            if ($this->is_video($params['media_url'])) {
                $endpoint = "/{$page_id}/videos";
                $post_data['file_url'] = $params['media_url'];
            } else {
                $endpoint = "/{$page_id}/photos";
                $post_data['url'] = $params['media_url'];
            }
        } else {
            $endpoint = "/{$page_id}/feed";
        }

        $response = wp_remote_post($this->api_endpoint . $endpoint, array(
            'body' => $post_data,
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['id'])) {
            return array(
                'success' => true,
                'post_id' => $data['id'],
                'data' => $data
            );
        }

        return array(
            'success' => false,
            'message' => $data['error']['message'] ?? 'Unknown error',
            'data' => $data
        );
    }

    /**
     * Connect Facebook account
     *
     * @param array $credentials
     * @return array
     */
    public function connect($credentials) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        $data = array(
            'user_id' => get_current_user_id(),
            'platform' => 'facebook',
            'account_name' => $credentials['account_name'] ?? '',
            'access_token' => $credentials['access_token'],
            'platform_user_id' => $credentials['page_id'] ?? '',
            'status' => 'active'
        );

        $existing = $this->get_connection(get_current_user_id());

        if ($existing) {
            $wpdb->update($table, $data, array(
                'user_id' => get_current_user_id(),
                'platform' => 'facebook'
            ));
        } else {
            $wpdb->insert($table, $data);
        }

        return array(
            'success' => true,
            'message' => 'Facebook connected successfully.'
        );
    }

    /**
     * Disconnect Facebook account
     *
     * @param int $user_id
     * @return bool
     */
    public function disconnect($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        return $wpdb->delete($table, array(
            'user_id' => $user_id,
            'platform' => 'facebook'
        ));
    }

    /**
     * Get connection
     *
     * @param int $user_id
     * @return array|null
     */
    private function get_connection($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND platform = 'facebook' AND status = 'active'",
            $user_id
        ), ARRAY_A);
    }

    /**
     * Get connection status
     *
     * @param int $user_id
     * @return array
     */
    public function get_status($user_id) {
        $connection = $this->get_connection($user_id);

        return array(
            'connected' => !empty($connection),
            'account_name' => $connection['account_name'] ?? '',
            'connected_at' => $connection['created_at'] ?? ''
        );
    }

    /**
     * Get analytics for a post
     *
     * @param string $post_id
     * @param int $user_id
     * @return array
     */
    public function get_analytics($post_id, $user_id) {
        $connection = $this->get_connection($user_id);

        if (!$connection) {
            return array('success' => false);
        }

        $access_token = $connection['access_token'];

        $response = wp_remote_get($this->api_endpoint . "/{$post_id}?fields=likes.summary(true),comments.summary(true),shares&access_token={$access_token}");

        if (is_wp_error($response)) {
            return array('success' => false);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return array(
            'success' => true,
            'likes' => $data['likes']['summary']['total_count'] ?? 0,
            'comments' => $data['comments']['summary']['total_count'] ?? 0,
            'shares' => $data['shares']['count'] ?? 0
        );
    }

    /**
     * Check if URL is a video
     *
     * @param string $url
     * @return bool
     */
    private function is_video($url) {
        $video_extensions = array('mp4', 'mov', 'avi', 'wmv', 'flv', 'webm');
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($ext, $video_extensions);
    }
}
