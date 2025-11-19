<?php
/**
 * Content Generator
 *
 * High-level content generation interface.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/content-generation
 */

class Kontent_Fire_Content_Generator {

    /**
     * AI Engine instance
     */
    private $ai_engine;

    /**
     * Database instance
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_engine = new Kontent_Fire_AI_Engine();
        $this->db = new Kontent_Fire_DB();
    }

    /**
     * Create and save content
     *
     * @param array $params
     * @return array
     */
    public function create_content($params) {
        $defaults = array(
            'type' => 'blog',
            'topic' => '',
            'platforms' => array(),
            'schedule' => false,
            'scheduled_time' => null,
            'auto_post' => false,
            'save_draft' => true
        );

        $params = wp_parse_args($params, $defaults);

        // Validate required fields
        if (empty($params['topic'])) {
            return array(
                'success' => false,
                'message' => 'Topic is required.'
            );
        }

        // Generate content
        $generation_result = $this->ai_engine->generate_content($params['type'], $params);

        if (!$generation_result['success']) {
            return $generation_result;
        }

        // Save to database if requested
        if ($params['save_draft']) {
            $saved = $this->save_content($generation_result, $params);
            if ($saved) {
                $generation_result['post_id'] = $saved;
            }
        }

        // Schedule if requested
        if ($params['schedule'] && !empty($params['scheduled_time'])) {
            $this->schedule_content($generation_result, $params);
        }

        return $generation_result;
    }

    /**
     * Save content to database
     *
     * @param array $content
     * @param array $params
     * @return int|false
     */
    private function save_content($content, $params) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        $data = array(
            'user_id' => get_current_user_id(),
            'platform' => $params['platforms'][0] ?? 'wordpress',
            'content' => is_array($content['content']) ? json_encode($content['content']) : $content['content'],
            'status' => $params['auto_post'] ? 'scheduled' : 'draft',
            'scheduled_time' => $params['scheduled_time'] ?? null,
            'created_at' => current_time('mysql')
        );

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Schedule content for posting
     *
     * @param array $content
     * @param array $params
     * @return bool
     */
    private function schedule_content($content, $params) {
        // This would integrate with the post scheduler
        $scheduler = new Kontent_Fire_Post_Scheduler();
        return $scheduler->schedule($content, $params);
    }

    /**
     * Generate content from URL
     *
     * @param string $url
     * @param array $options
     * @return array
     */
    public function generate_from_url($url, $options = array()) {
        // Fetch URL content
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Failed to fetch URL: ' . $response->get_error_message()
            );
        }

        $html = wp_remote_retrieve_body($response);

        // Extract main content (basic implementation)
        $content = $this->extract_content_from_html($html);

        // Generate social posts from content
        $platforms = $options['platforms'] ?? array('facebook', 'twitter', 'linkedin');

        $results = array();
        foreach ($platforms as $platform) {
            $prompt = "Summarize this content for {$platform}:\n\n{$content}";
            $results[$platform] = $this->ai_engine->generate_content('social', array(
                'prompt' => $prompt,
                'platform' => $platform
            ));
        }

        return array(
            'success' => true,
            'source_url' => $url,
            'results' => $results
        );
    }

    /**
     * Extract content from HTML
     *
     * @param string $html
     * @return string
     */
    private function extract_content_from_html($html) {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Strip all HTML tags
        $text = strip_tags($html);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Limit to first 3000 characters for processing
        return substr($text, 0, 3000);
    }

    /**
     * Batch generate content
     *
     * @param array $topics
     * @param array $options
     * @return array
     */
    public function batch_generate($topics, $options = array()) {
        $results = array();

        foreach ($topics as $topic) {
            $params = array_merge($options, array('topic' => $topic));
            $results[$topic] = $this->create_content($params);

            // Add delay to avoid rate limiting
            sleep(2);
        }

        return array(
            'success' => true,
            'results' => $results
        );
    }

    /**
     * Generate content calendar
     *
     * @param array $params
     * @return array
     */
    public function generate_calendar($params) {
        $niche = $params['niche'] ?? 'general';
        $days = $params['days'] ?? 30;

        $calendar = $this->ai_engine->generate_content('content_calendar', array(
            'niche' => $niche,
            'days' => $days
        ));

        if ($calendar['success']) {
            // Save calendar to database
            $this->save_calendar($calendar['content'], $params);
        }

        return $calendar;
    }

    /**
     * Save content calendar
     *
     * @param string $calendar_data
     * @param array $params
     * @return bool
     */
    private function save_calendar($calendar_data, $params) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_content_pipeline';

        $data = array(
            'user_id' => get_current_user_id(),
            'source_type' => 'content_calendar',
            'content_data' => $calendar_data,
            'ai_analysis' => json_encode($params),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );

        return $wpdb->insert($table, $data);
    }

    /**
     * Get content suggestions
     *
     * @param array $params
     * @return array
     */
    public function get_suggestions($params) {
        $industry = $params['industry'] ?? 'general';

        return $this->ai_engine->generate_content('trending', array(
            'industry' => $industry,
            'platform' => $params['platform'] ?? 'all platforms',
            'audience' => $params['audience'] ?? 'general'
        ));
    }

    /**
     * Repurpose content
     *
     * @param int $post_id
     * @param array $target_platforms
     * @return array
     */
    public function repurpose_content($post_id, $target_platforms) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $post_id
        ), ARRAY_A);

        if (!$post) {
            return array(
                'success' => false,
                'message' => 'Post not found.'
            );
        }

        $results = array();
        foreach ($target_platforms as $platform) {
            $results[$platform] = $this->ai_engine->generate_content('optimize', array(
                'content' => $post['content'],
                'source_platform' => $post['platform'],
                'target_platform' => $platform
            ));
        }

        return array(
            'success' => true,
            'results' => $results
        );
    }
}
