<?php
/**
 * Post Scheduler
 *
 * Schedules posts for future publishing.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/scheduler
 */

class Kontent_Fire_Post_Scheduler {

    /**
     * Database instance
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Kontent_Fire_DB();

        // Hook into WordPress cron
        add_action('kontent_fire_process_queue', array($this, 'process_scheduled_posts'));
    }

    /**
     * Schedule a post
     *
     * @param array $content
     * @param array $params
     * @return int|false
     */
    public function schedule($content, $params) {
        $post_data = array(
            'user_id' => get_current_user_id(),
            'platform' => $params['platform'] ?? 'wordpress',
            'content' => is_array($content) ? json_encode($content) : $content,
            'media_url' => $params['media_url'] ?? '',
            'status' => 'scheduled',
            'scheduled_time' => $params['scheduled_time'],
        );

        return $this->db->create_post($post_data);
    }

    /**
     * Process scheduled posts
     */
    public function process_scheduled_posts() {
        $posts = $this->db->get_scheduled_posts();

        foreach ($posts as $post) {
            $this->publish_post($post);
        }
    }

    /**
     * Publish a post
     *
     * @param array $post
     * @return bool
     */
    private function publish_post($post) {
        $queue_manager = new Kontent_Fire_Queue_Manager();
        return $queue_manager->add_to_queue($post);
    }

    /**
     * Cancel scheduled post
     *
     * @param int $post_id
     * @return bool
     */
    public function cancel($post_id) {
        return $this->db->update_post($post_id, array('status' => 'cancelled'));
    }

    /**
     * Reschedule post
     *
     * @param int $post_id
     * @param string $new_time
     * @return bool
     */
    public function reschedule($post_id, $new_time) {
        return $this->db->update_post($post_id, array(
            'scheduled_time' => $new_time,
            'status' => 'scheduled'
        ));
    }

    /**
     * Get optimal posting time
     *
     * @param string $platform
     * @param int $user_id
     * @return string
     */
    public function get_optimal_time($platform, $user_id = null) {
        // This would analyze past engagement data
        // For now, return default optimal times
        $optimal_times = array(
            'facebook' => '13:00:00',  // 1 PM
            'instagram' => '11:00:00', // 11 AM
            'twitter' => '12:00:00',   // 12 PM
            'linkedin' => '08:00:00',  // 8 AM
            'tiktok' => '19:00:00',    // 7 PM
            'youtube' => '14:00:00',   // 2 PM
        );

        $time = $optimal_times[$platform] ?? '12:00:00';

        return date('Y-m-d') . ' ' . $time;
    }

    /**
     * Bulk schedule posts
     *
     * @param array $posts
     * @param array $schedule_params
     * @return array
     */
    public function bulk_schedule($posts, $schedule_params) {
        $results = array();
        $interval = $schedule_params['interval'] ?? 3600; // Default 1 hour between posts
        $start_time = strtotime($schedule_params['start_time'] ?? 'now');

        foreach ($posts as $index => $post) {
            $scheduled_time = date('Y-m-d H:i:s', $start_time + ($index * $interval));

            $post['scheduled_time'] = $scheduled_time;
            $result = $this->schedule($post, $post);

            $results[] = array(
                'post' => $post,
                'scheduled_time' => $scheduled_time,
                'post_id' => $result
            );
        }

        return $results;
    }
}
