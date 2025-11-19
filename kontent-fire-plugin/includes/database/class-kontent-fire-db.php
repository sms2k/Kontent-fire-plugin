<?php
/**
 * Database Helper
 *
 * Handles database operations for the plugin.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/database
 */

class Kontent_Fire_DB {

    /**
     * Get post by ID
     *
     * @param int $post_id
     * @return array|null
     */
    public function get_post($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $post_id
        ), ARRAY_A);
    }

    /**
     * Get posts by user
     *
     * @param int $user_id
     * @param array $args
     * @return array
     */
    public function get_user_posts($user_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        $defaults = array(
            'status' => '',
            'platform' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array("user_id = %d");
        $values = array($user_id);

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }

        if (!empty($args['platform'])) {
            $where[] = "platform = %s";
            $values[] = $args['platform'];
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($query, $values), ARRAY_A);
    }

    /**
     * Create post
     *
     * @param array $data
     * @return int|false
     */
    public function create_post($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        $defaults = array(
            'user_id' => get_current_user_id(),
            'platform' => 'wordpress',
            'content' => '',
            'status' => 'draft',
            'created_at' => current_time('mysql')
        );

        $data = wp_parse_args($data, $defaults);

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Update post
     *
     * @param int $post_id
     * @param array $data
     * @return bool
     */
    public function update_post($post_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        $data['updated_at'] = current_time('mysql');

        return $wpdb->update($table, $data, array('id' => $post_id));
    }

    /**
     * Delete post
     *
     * @param int $post_id
     * @return bool
     */
    public function delete_post($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        return $wpdb->delete($table, array('id' => $post_id));
    }

    /**
     * Get scheduled posts
     *
     * @return array
     */
    public function get_scheduled_posts() {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        return $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'scheduled' AND scheduled_time <= NOW() ORDER BY scheduled_time ASC",
            ARRAY_A
        );
    }

    /**
     * Get analytics for post
     *
     * @param int $post_id
     * @return array
     */
    public function get_post_analytics($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_analytics';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d ORDER BY recorded_at DESC",
            $post_id
        ), ARRAY_A);
    }

    /**
     * Save analytics data
     *
     * @param int $post_id
     * @param string $platform
     * @param array $metrics
     * @return bool
     */
    public function save_analytics($post_id, $platform, $metrics) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_analytics';

        $success = true;
        foreach ($metrics as $metric_name => $metric_value) {
            $result = $wpdb->insert($table, array(
                'post_id' => $post_id,
                'platform' => $platform,
                'metric_name' => $metric_name,
                'metric_value' => $metric_value,
                'recorded_at' => current_time('mysql')
            ));

            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }
}
