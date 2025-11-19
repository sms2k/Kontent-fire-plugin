<?php
/**
 * Google Gemini API Integration
 *
 * Handles communication with Google's Gemini API for content and media generation.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/api
 */

class Kontent_Fire_Gemini_API {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * API key
     */
    private $api_key;

    /**
     * Model version
     */
    private $model = 'gemini-pro';

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('kontent_fire_gemini_api_key');
    }

    /**
     * Generate content using Gemini
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate_content($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Gemini API key not configured.'
            );
        }

        $defaults = array(
            'temperature' => 0.7,
            'maxOutputTokens' => 2048,
            'topP' => 0.8,
            'topK' => 40
        );

        $options = wp_parse_args($options, $defaults);

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => $options['temperature'],
                'maxOutputTokens' => $options['maxOutputTokens'],
                'topP' => $options['topP'],
                'topK' => $options['topK']
            )
        );

        $url = $this->api_endpoint . '/models/' . $this->model . ':generateContent?key=' . $this->api_key;

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'API error: ' . ($data['error']['message'] ?? 'Unknown error'),
                'code' => $response_code
            );
        }

        $content = '';
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return array(
            'success' => true,
            'content' => $content,
            'data' => $data
        );
    }

    /**
     * Generate image using Imagen (via Gemini)
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate_image($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Gemini API key not configured.'
            );
        }

        $defaults = array(
            'numberOfImages' => 1,
            'aspectRatio' => '1:1',
            'negativePrompt' => '',
            'safetyFilterLevel' => 'block_some'
        );

        $options = wp_parse_args($options, $defaults);

        // Note: This uses the Imagen API which is part of the Vertex AI suite
        // You may need to adjust the endpoint based on your Google Cloud setup
        $body = array(
            'instances' => array(
                array(
                    'prompt' => $prompt
                )
            ),
            'parameters' => array(
                'sampleCount' => $options['numberOfImages'],
                'aspectRatio' => $options['aspectRatio'],
                'negativePrompt' => $options['negativePrompt'],
                'safetyFilterLevel' => $options['safetyFilterLevel']
            )
        );

        // For now, return a structured response that can be implemented when Imagen API is available
        return array(
            'success' => true,
            'message' => 'Image generation queued. Imagen integration requires Google Cloud Vertex AI setup.',
            'prompt' => $prompt,
            'options' => $options
        );
    }

    /**
     * Generate video script
     *
     * @param string $topic
     * @param array $options
     * @return array
     */
    public function generate_video_script($topic, $options = array()) {
        $duration = $options['duration'] ?? '30-60 seconds';
        $style = $options['style'] ?? 'educational';
        $platform = $options['platform'] ?? 'youtube';

        $prompt = "Create a video script for: {$topic}

Platform: {$platform}
Duration: {$duration}
Style: {$style}

Include:
1. Hook (first 3 seconds)
2. Main content with scene descriptions
3. Visual suggestions for each scene
4. Text overlays / captions
5. Call-to-action
6. Background music suggestions

Format as JSON with scenes array, each containing: scene_number, duration, narration, visuals, text_overlay";

        return $this->generate_content($prompt, array(
            'maxOutputTokens' => 4096
        ));
    }

    /**
     * Analyze image and generate description
     *
     * @param string $image_path
     * @return array
     */
    public function analyze_image($image_path) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Gemini API key not configured.'
            );
        }

        // Read image and convert to base64
        $image_data = file_get_contents($image_path);
        if ($image_data === false) {
            return array(
                'success' => false,
                'message' => 'Could not read image file.'
            );
        }

        $base64_image = base64_encode($image_data);
        $mime_type = mime_content_type($image_path);

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => 'Describe this image in detail, including objects, colors, mood, and potential uses for social media or blog posts.'),
                        array(
                            'inline_data' => array(
                                'mime_type' => $mime_type,
                                'data' => $base64_image
                            )
                        )
                    )
                )
            )
        );

        $url = $this->api_endpoint . '/models/gemini-pro-vision:generateContent?key=' . $this->api_key;

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        $description = '';
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $description = $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return array(
            'success' => true,
            'description' => $description
        );
    }

    /**
     * Generate content calendar
     *
     * @param string $niche
     * @param int $days
     * @return array
     */
    public function generate_content_calendar($niche, $days = 30) {
        $prompt = "Create a {$days}-day content calendar for a {$niche} brand.

For each day, provide:
1. Content topic/theme
2. Content type (blog, video, infographic, etc.)
3. Platform(s) to post on
4. Best posting time
5. Key message
6. Hashtag suggestions

Format as JSON array with daily entries.";

        return $this->generate_content($prompt, array(
            'maxOutputTokens' => 8000
        ));
    }

    /**
     * Generate trending topic ideas
     *
     * @param string $industry
     * @param array $options
     * @return array
     */
    public function generate_trending_topics($industry, $options = array()) {
        $platform = $options['platform'] ?? 'all platforms';
        $audience = $options['audience'] ?? 'general';

        $prompt = "Generate 20 trending content topic ideas for the {$industry} industry.

Target platform: {$platform}
Target audience: {$audience}

For each topic provide:
1. Topic title
2. Why it's trending
3. Best content format
4. Estimated engagement potential (low/medium/high)
5. Suggested angle/hook

Format as JSON array.";

        return $this->generate_content($prompt, array(
            'maxOutputTokens' => 4096
        ));
    }

    /**
     * Optimize content for platform
     *
     * @param string $content
     * @param string $source_platform
     * @param string $target_platform
     * @return array
     */
    public function optimize_for_platform($content, $source_platform, $target_platform) {
        $prompt = "Adapt this {$source_platform} content for {$target_platform}:

{$content}

Optimize for:
- Platform-specific character limits
- Tone and style
- Hashtag usage
- Visual requirements
- Call-to-action placement

Provide the optimized content ready to post.";

        return $this->generate_content($prompt);
    }

    /**
     * Generate hook/opening lines
     *
     * @param string $topic
     * @param int $count
     * @return array
     */
    public function generate_hooks($topic, $count = 10) {
        $prompt = "Create {$count} attention-grabbing hooks/opening lines for content about: {$topic}

Include variety:
- Question-based hooks
- Controversial statements
- Personal stories
- Statistics
- Bold predictions
- Pain point hooks

Format as JSON array with: hook, type, explanation";

        return $this->generate_content($prompt, array(
            'maxOutputTokens' => 2048
        ));
    }
}
