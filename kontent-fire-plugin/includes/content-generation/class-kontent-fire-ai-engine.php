<?php
/**
 * AI Engine
 *
 * Coordinates between different AI APIs and manages content generation workflow.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/content-generation
 */

class Kontent_Fire_AI_Engine {

    /**
     * API instances
     */
    private $claude_api;
    private $openai_api;
    private $gemini_api;

    /**
     * License manager
     */
    private $license_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->claude_api = new Kontent_Fire_Claude_API();
        $this->openai_api = new Kontent_Fire_OpenAI_API();
        $this->gemini_api = new Kontent_Fire_Gemini_API();
        $this->license_manager = new Kontent_Fire_License_Manager();
    }

    /**
     * Check if feature is available
     *
     * @param string $feature
     * @return bool
     */
    private function check_feature_access($feature) {
        return $this->license_manager->has_feature($feature);
    }

    /**
     * Generate content using the best available API
     *
     * @param string $type Content type (blog, social, email, etc.)
     * @param array $params Parameters for generation
     * @return array
     */
    public function generate_content($type, $params = array()) {
        if (!$this->check_feature_access('content_generation')) {
            return array(
                'success' => false,
                'message' => 'Content generation is not available in your plan. Please upgrade.'
            );
        }

        // Determine which API to use based on content type and availability
        $preferred_api = $params['preferred_api'] ?? $this->get_preferred_api($type);

        switch ($preferred_api) {
            case 'claude':
                return $this->generate_with_claude($type, $params);
            case 'openai':
                return $this->generate_with_openai($type, $params);
            case 'gemini':
                return $this->generate_with_gemini($type, $params);
            default:
                // Try fallback chain: Claude -> OpenAI -> Gemini
                return $this->generate_with_fallback($type, $params);
        }
    }

    /**
     * Get preferred API for content type
     *
     * @param string $type
     * @return string
     */
    private function get_preferred_api($type) {
        $preferences = array(
            'blog' => 'claude',           // Claude excels at long-form content
            'social' => 'claude',          // Claude is great for creative social content
            'seo' => 'claude',             // Claude for SEO analysis
            'image' => 'openai',           // DALL-E for images
            'video_script' => 'gemini',    // Gemini for video content
            'keyword_research' => 'claude', // Claude for research
            'content_calendar' => 'gemini' // Gemini for planning
        );

        return $preferences[$type] ?? 'claude';
    }

    /**
     * Generate with Claude
     *
     * @param string $type
     * @param array $params
     * @return array
     */
    private function generate_with_claude($type, $params) {
        switch ($type) {
            case 'blog':
                return $this->claude_api->generate_blog_post(
                    $params['topic'],
                    $params
                );

            case 'social':
                return $this->claude_api->generate_social_caption(
                    $params['topic'],
                    $params['platform'],
                    $params
                );

            case 'seo':
                return $this->claude_api->analyze_seo(
                    $params['content'],
                    $params['keyword'] ?? ''
                );

            case 'keyword_research':
                return $this->claude_api->research_keywords(
                    $params['topic'],
                    $params['industry'] ?? ''
                );

            case 'meme':
                return $this->claude_api->generate_meme_text(
                    $params['topic'],
                    $params['template'] ?? ''
                );

            case 'variations':
                return $this->claude_api->generate_variations(
                    $params['content'],
                    $params['count'] ?? 3
                );

            default:
                return $this->claude_api->generate_content($params['prompt'] ?? '');
        }
    }

    /**
     * Generate with OpenAI
     *
     * @param string $type
     * @param array $params
     * @return array
     */
    private function generate_with_openai($type, $params) {
        switch ($type) {
            case 'image':
                return $this->openai_api->generate_image(
                    $params['prompt'],
                    $params
                );

            case 'moderate':
                return $this->openai_api->moderate_content($params['content']);

            default:
                return $this->openai_api->generate_content($params['prompt'] ?? '');
        }
    }

    /**
     * Generate with Gemini
     *
     * @param string $type
     * @param array $params
     * @return array
     */
    private function generate_with_gemini($type, $params) {
        switch ($type) {
            case 'video_script':
                return $this->gemini_api->generate_video_script(
                    $params['topic'],
                    $params
                );

            case 'content_calendar':
                return $this->gemini_api->generate_content_calendar(
                    $params['niche'],
                    $params['days'] ?? 30
                );

            case 'trending':
                return $this->gemini_api->generate_trending_topics(
                    $params['industry'],
                    $params
                );

            case 'hooks':
                return $this->gemini_api->generate_hooks(
                    $params['topic'],
                    $params['count'] ?? 10
                );

            case 'optimize':
                return $this->gemini_api->optimize_for_platform(
                    $params['content'],
                    $params['source_platform'],
                    $params['target_platform']
                );

            default:
                return $this->gemini_api->generate_content($params['prompt'] ?? '');
        }
    }

    /**
     * Generate with fallback chain
     *
     * @param string $type
     * @param array $params
     * @return array
     */
    private function generate_with_fallback($type, $params) {
        $apis = array('claude', 'openai', 'gemini');
        $errors = array();

        foreach ($apis as $api) {
            $params['preferred_api'] = $api;
            $result = $this->generate_content($type, $params);

            if ($result['success']) {
                return $result;
            }

            $errors[$api] = $result['message'] ?? 'Unknown error';
        }

        return array(
            'success' => false,
            'message' => 'All AI APIs failed.',
            'errors' => $errors
        );
    }

    /**
     * Generate multi-platform content
     *
     * @param string $topic
     * @param array $platforms
     * @param array $options
     * @return array
     */
    public function generate_multi_platform_content($topic, $platforms, $options = array()) {
        if (!$this->check_feature_access('multi_platform')) {
            return array(
                'success' => false,
                'message' => 'Multi-platform content generation is not available in your plan.'
            );
        }

        $results = array();

        foreach ($platforms as $platform) {
            $params = array_merge($options, array(
                'topic' => $topic,
                'platform' => $platform
            ));

            $results[$platform] = $this->generate_content('social', $params);
        }

        return array(
            'success' => true,
            'results' => $results
        );
    }

    /**
     * Generate complete content package
     *
     * @param string $topic
     * @param array $options
     * @return array
     */
    public function generate_content_package($topic, $options = array()) {
        $package = array(
            'topic' => $topic,
            'created_at' => current_time('mysql')
        );

        // Generate blog post
        if ($options['include_blog'] ?? true) {
            $package['blog'] = $this->generate_content('blog', array(
                'topic' => $topic,
                'tone' => $options['tone'] ?? 'professional',
                'length' => $options['blog_length'] ?? 'medium',
                'keywords' => $options['keywords'] ?? array()
            ));
        }

        // Generate social media posts
        if ($options['include_social'] ?? true) {
            $platforms = $options['platforms'] ?? array('facebook', 'instagram', 'twitter', 'linkedin');
            $package['social'] = $this->generate_multi_platform_content($topic, $platforms, $options);
        }

        // Generate images
        if (($options['include_images'] ?? true) && $this->check_feature_access('image_generation')) {
            $image_prompt = $options['image_prompt'] ?? "Professional image for: {$topic}";
            $package['images'] = $this->generate_content('image', array(
                'prompt' => $image_prompt,
                'n' => $options['image_count'] ?? 1
            ));
        }

        // Generate video script
        if (($options['include_video'] ?? false) && $this->check_feature_access('video_generation')) {
            $package['video_script'] = $this->generate_content('video_script', array(
                'topic' => $topic,
                'duration' => $options['video_duration'] ?? '30-60 seconds',
                'platform' => $options['video_platform'] ?? 'youtube'
            ));
        }

        // Perform SEO analysis
        if (($options['include_seo'] ?? true) && $this->check_feature_access('advanced_seo')) {
            if (isset($package['blog']['content'])) {
                // Parse the blog content from JSON if needed
                $blog_content = $package['blog']['content'];
                if ($this->is_json($blog_content)) {
                    $blog_data = json_decode($blog_content, true);
                    $content_to_analyze = $blog_data['body'] ?? $blog_content;
                } else {
                    $content_to_analyze = $blog_content;
                }

                $package['seo'] = $this->generate_content('seo', array(
                    'content' => $content_to_analyze,
                    'keyword' => $options['target_keyword'] ?? $topic
                ));
            }
        }

        return array(
            'success' => true,
            'package' => $package
        );
    }

    /**
     * Check if string is JSON
     *
     * @param string $string
     * @return bool
     */
    private function is_json($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get AI usage statistics
     *
     * @param int $user_id
     * @return array
     */
    public function get_usage_stats($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // This would track API usage in a separate table
        // For now, return a basic structure
        return array(
            'user_id' => $user_id,
            'total_generations' => 0,
            'this_month' => 0,
            'api_costs' => array(
                'claude' => 0,
                'openai' => 0,
                'gemini' => 0
            )
        );
    }
}
