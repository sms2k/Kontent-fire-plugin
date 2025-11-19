<?php
/**
 * Blog Promotion Engine
 *
 * Automatically creates and publishes social media posts promoting blog content.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/content-generation
 */

class Kontent_Fire_Blog_Promoter {

    /**
     * AI Engine instance
     */
    private $ai_engine;

    /**
     * Social API Manager
     */
    private $social_manager;

    /**
     * Post Scheduler
     */
    private $scheduler;

    /**
     * Database instance
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_engine = new Kontent_Fire_AI_Engine();
        $this->social_manager = new Kontent_Fire_Social_API_Manager();
        $this->scheduler = new Kontent_Fire_Post_Scheduler();
        $this->db = new Kontent_Fire_DB();

        // Hook into WordPress post publication
        add_action('publish_post', array($this, 'auto_promote_blog'), 10, 2);
        add_action('publish_page', array($this, 'auto_promote_blog'), 10, 2);
    }

    /**
     * Automatically promote blog when published
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function auto_promote_blog($post_id, $post) {
        // Check if auto-promotion is enabled
        if (get_option('kontent_fire_auto_promote_blogs', 'yes') !== 'yes') {
            return;
        }

        // Prevent infinite loops
        if (get_post_meta($post_id, '_kf_auto_promoted', true)) {
            return;
        }

        // Mark as being promoted
        update_post_meta($post_id, '_kf_auto_promoted', time());

        // Get blog data
        $blog_url = get_permalink($post_id);
        $blog_title = $post->post_title;
        $blog_excerpt = wp_trim_words($post->post_content, 50, '...');
        $featured_image = get_the_post_thumbnail_url($post_id, 'large');

        // Get connected platforms
        $platforms = $this->get_enabled_promotion_platforms();

        if (empty($platforms)) {
            return;
        }

        // Generate promotional content for each platform
        $this->create_promotional_posts($blog_title, $blog_excerpt, $blog_url, $featured_image, $platforms, $post_id);
    }

    /**
     * Create promotional posts for all enabled platforms
     *
     * @param string $title
     * @param string $excerpt
     * @param string $url
     * @param string $image_url
     * @param array $platforms
     * @param int $blog_post_id
     */
    private function create_promotional_posts($title, $excerpt, $url, $image_url, $platforms, $blog_post_id) {
        $claude_api = new Kontent_Fire_Claude_API();

        foreach ($platforms as $platform) {
            // Generate engaging social post using Claude for final copywriting
            $prompt = "Create an amazing, highly engaging {$platform} post promoting this blog article:

Title: {$title}
Summary: {$excerpt}
URL: {$url}

Requirements:
- Make it attention-grabbing and compelling
- Use platform-specific best practices
- Include appropriate hashtags (3-5 relevant ones)
- Add a clear call-to-action
- Optimize for maximum engagement
- Keep within platform character limits
- Use emojis strategically if appropriate for the platform

Provide ONLY the final post text ready to publish, nothing else.";

            $result = $claude_api->generate_content($prompt, array(
                'max_tokens' => 500,
                'temperature' => 0.9,  // Higher creativity for social posts
                'system' => 'You are an expert social media copywriter who creates viral, engaging posts that drive clicks and engagement. You understand platform-specific nuances and write compelling copy that resonates with audiences.'
            ));

            if ($result['success']) {
                $post_content = $result['content'];

                // Polish the content with Claude for final touch
                $polish_result = $this->polish_with_claude($post_content, $platform);
                if ($polish_result['success']) {
                    $post_content = $polish_result['content'];
                }

                // Schedule the promotional post
                $scheduled_time = $this->calculate_optimal_post_time($platform);

                $post_data = array(
                    'user_id' => get_current_user_id(),
                    'platform' => $platform,
                    'content' => $post_content,
                    'media_url' => $image_url,
                    'status' => 'scheduled',
                    'scheduled_time' => $scheduled_time,
                    'created_at' => current_time('mysql')
                );

                $scheduled_post_id = $this->db->create_post($post_data);

                // Link promotional post to original blog
                if ($scheduled_post_id) {
                    update_post_meta($blog_post_id, '_kf_promo_post_' . $platform, $scheduled_post_id);
                }
            }
        }
    }

    /**
     * Polish content with Claude for final copywriting
     *
     * @param string $content
     * @param string $platform
     * @return array
     */
    private function polish_with_claude($content, $platform) {
        $claude_api = new Kontent_Fire_Claude_API();

        $prompt = "Polish and perfect this {$platform} post for maximum engagement. Make it even more compelling while keeping the same core message:

{$content}

Enhance:
- Opening hook (make it irresistible)
- Emotional appeal
- Clarity and readability
- Call-to-action strength
- Overall impact

Return ONLY the polished version, ready to post.";

        return $claude_api->generate_content($prompt, array(
            'max_tokens' => 500,
            'temperature' => 0.7,
            'system' => 'You are a master copywriter who perfects social media posts. You make every word count and optimize for maximum engagement.'
        ));
    }

    /**
     * Calculate optimal posting time for platform
     *
     * @param string $platform
     * @return string
     */
    private function calculate_optimal_post_time($platform) {
        // Get delay setting (default: post immediately)
        $delay_hours = (int) get_option('kontent_fire_promo_delay_hours', 0);

        // Platform-specific optimal times
        $optimal_times = array(
            'facebook' => '+' . ($delay_hours ?: 2) . ' hours',
            'instagram' => '+' . ($delay_hours ?: 3) . ' hours',
            'twitter' => '+' . ($delay_hours ?: 1) . ' hours',
            'linkedin' => '+' . ($delay_hours ?: 4) . ' hours',
            'pinterest' => '+' . ($delay_hours ?: 5) . ' hours'
        );

        $delay = $optimal_times[$platform] ?? '+2 hours';

        return date('Y-m-d H:i:s', strtotime($delay));
    }

    /**
     * Get platforms enabled for auto-promotion
     *
     * @return array
     */
    private function get_enabled_promotion_platforms() {
        $enabled = get_option('kontent_fire_auto_promo_platforms', array('facebook', 'twitter', 'linkedin'));

        if (is_string($enabled)) {
            $enabled = json_decode($enabled, true) ?: array();
        }

        // Filter to only connected platforms
        $connected = array();
        foreach ($enabled as $platform) {
            $status = $this->social_manager->get_connection_status($platform, get_current_user_id());
            if ($status['connected']) {
                $connected[] = $platform;
            }
        }

        return $connected;
    }

    /**
     * Manually promote a blog post
     *
     * @param int $post_id
     * @param array $platforms
     * @return array
     */
    public function manual_promote($post_id, $platforms) {
        $post = get_post($post_id);

        if (!$post) {
            return array(
                'success' => false,
                'message' => 'Post not found.'
            );
        }

        $blog_url = get_permalink($post_id);
        $blog_title = $post->post_title;
        $blog_excerpt = wp_trim_words($post->post_content, 50, '...');
        $featured_image = get_the_post_thumbnail_url($post_id, 'large');

        $this->create_promotional_posts($blog_title, $blog_excerpt, $blog_url, $featured_image, $platforms, $post_id);

        return array(
            'success' => true,
            'message' => 'Promotional posts scheduled for ' . implode(', ', $platforms)
        );
    }

    /**
     * Create variation posts for A/B testing
     *
     * @param string $original_content
     * @param string $platform
     * @param int $variations
     * @return array
     */
    public function create_promotion_variations($original_content, $platform, $variations = 3) {
        $claude_api = new Kontent_Fire_Claude_API();

        return $claude_api->generate_variations($original_content, $variations);
    }
}
