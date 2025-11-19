<?php
/**
 * Queue Manager
 *
 * Manages the posting queue and handles actual posting to platforms.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/scheduler
 */

class Kontent_Fire_Queue_Manager {

    /**
     * Database instance
     */
    private $db;

    /**
     * Social API Manager
     */
    private $social_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Kontent_Fire_DB();
        $this->social_manager = new Kontent_Fire_Social_API_Manager();
    }

    /**
     * Add post to queue
     *
     * @param array $post
     * @return bool
     */
    public function add_to_queue($post) {
        // Update status to queued
        $this->db->update_post($post['id'], array('status' => 'queued'));

        // Process immediately (in production, this might be async)
        return $this->process_post($post);
    }

    /**
     * Process a post
     *
     * @param array $post
     * @return bool
     */
    private function process_post($post) {
        $platform = $post['platform'];

        try {
            // Get platform handler
            $result = $this->social_manager->post_content($platform, array(
                'content' => $post['content'],
                'media_url' => $post['media_url'] ?? null,
                'user_id' => $post['user_id']
            ));

            if ($result['success']) {
                // Update post status
                $this->db->update_post($post['id'], array(
                    'status' => 'published',
                    'posted_time' => current_time('mysql'),
                    'platform_post_id' => $result['post_id'] ?? '',
                    'response' => json_encode($result)
                ));

                return true;
            } else {
                // Mark as failed
                $this->db->update_post($post['id'], array(
                    'status' => 'failed',
                    'response' => json_encode($result)
                ));

                return false;
            }
        } catch (Exception $e) {
            // Log error
            $this->db->update_post($post['id'], array(
                'status' => 'failed',
                'response' => json_encode(array('error' => $e->getMessage()))
            ));

            return false;
        }
    }

    /**
     * Retry failed post
     *
     * @param int $post_id
     * @return bool
     */
    public function retry($post_id) {
        $post = $this->db->get_post($post_id);

        if (!$post || $post['status'] !== 'failed') {
            return false;
        }

        return $this->add_to_queue($post);
    }

    /**
     * Get queue status
     *
     * @return array
     */
    public function get_queue_status() {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $table GROUP BY status",
            ARRAY_A
        );

        $status = array(
            'queued' => 0,
            'scheduled' => 0,
            'published' => 0,
            'failed' => 0,
            'draft' => 0
        );

        foreach ($status_counts as $row) {
            $status[$row['status']] = (int)$row['count'];
        }

        return $status;
    }
}
