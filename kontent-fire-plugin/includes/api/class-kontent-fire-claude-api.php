<?php
/**
 * Claude API Integration
 *
 * Handles communication with Anthropic's Claude API.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/api
 */

class Kontent_Fire_Claude_API {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://api.anthropic.com/v1/messages';

    /**
     * API key
     */
    private $api_key;

    /**
     * Model version
     */
    private $model = 'claude-3-5-sonnet-20241022';

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('kontent_fire_claude_api_key');
    }

    /**
     * Generate content using Claude
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate_content($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Claude API key not configured.'
            );
        }

        $defaults = array(
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'system' => 'You are an expert content creator and SEO specialist.',
        );

        $options = wp_parse_args($options, $defaults);

        $body = array(
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
            'system' => $options['system'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );

        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
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

        return array(
            'success' => true,
            'content' => $data['content'][0]['text'] ?? '',
            'usage' => $data['usage'] ?? array()
        );
    }

    /**
     * Generate blog post
     *
     * @param string $topic
     * @param array $options
     * @return array
     */
    public function generate_blog_post($topic, $options = array()) {
        $tone = $options['tone'] ?? 'professional';
        $length = $options['length'] ?? 'medium';
        $keywords = $options['keywords'] ?? array();

        $keywords_str = !empty($keywords) ? 'Include these keywords naturally: ' . implode(', ', $keywords) : '';

        $prompt = "Write a comprehensive blog post about: {$topic}

Tone: {$tone}
Length: {$length} (short=500 words, medium=1000 words, long=2000+ words)
{$keywords_str}

Please provide:
1. An engaging title
2. A compelling introduction
3. Well-structured body with subheadings
4. A strong conclusion
5. Meta description (150-160 characters)

Format the response as JSON with keys: title, introduction, body, conclusion, meta_description, suggested_tags";

        return $this->generate_content($prompt, array(
            'max_tokens' => 8000,
            'system' => 'You are an expert content writer specializing in SEO-optimized blog posts. Always respond with valid JSON.'
        ));
    }

    /**
     * Generate social media caption
     *
     * @param string $topic
     * @param string $platform
     * @param array $options
     * @return array
     */
    public function generate_social_caption($topic, $platform, $options = array()) {
        $tone = $options['tone'] ?? 'engaging';
        $include_hashtags = $options['include_hashtags'] ?? true;
        $include_emoji = $options['include_emoji'] ?? true;

        $platform_specs = array(
            'facebook' => 'casual and engaging, 1-3 paragraphs',
            'instagram' => 'visual and inspiring, use line breaks, 2-3 paragraphs',
            'twitter' => 'concise and punchy, max 280 characters',
            'linkedin' => 'professional and insightful, 2-4 paragraphs',
            'tiktok' => 'trendy and fun, use popular slang',
            'youtube' => 'informative with timestamps, include CTA'
        );

        $style = $platform_specs[$platform] ?? 'engaging and appropriate for the platform';

        $prompt = "Create a {$platform} post about: {$topic}

Style: {$style}
Tone: {$tone}
" . ($include_hashtags ? "Include relevant hashtags" : "No hashtags") . "
" . ($include_emoji ? "Use emojis appropriately" : "No emojis") . "

Provide the caption ready to post.";

        return $this->generate_content($prompt, array(
            'max_tokens' => 1024,
            'system' => "You are a social media expert who creates viral, engaging content for {$platform}."
        ));
    }

    /**
     * Analyze content for SEO
     *
     * @param string $content
     * @param string $target_keyword
     * @return array
     */
    public function analyze_seo($content, $target_keyword = '') {
        $prompt = "Analyze this content for SEO and provide recommendations:

Content:
{$content}

" . ($target_keyword ? "Target keyword: {$target_keyword}" : "") . "

Provide a detailed analysis including:
1. SEO score (0-100)
2. Readability score (0-100)
3. Keyword density and placement
4. Suggestions for improvement
5. Recommended meta title and description
6. Additional keywords to target

Format as JSON with keys: seo_score, readability_score, keyword_analysis, suggestions, meta_title, meta_description, additional_keywords";

        return $this->generate_content($prompt, array(
            'max_tokens' => 2048,
            'system' => 'You are an SEO expert who analyzes content and provides actionable recommendations. Always respond with valid JSON.'
        ));
    }

    /**
     * Generate content variations (A/B testing)
     *
     * @param string $original_content
     * @param int $variations
     * @return array
     */
    public function generate_variations($original_content, $variations = 3) {
        $prompt = "Create {$variations} different variations of this content for A/B testing:

Original:
{$original_content}

Each variation should have a different approach (e.g., different hook, different tone, different structure) while maintaining the core message.

Format as JSON array with each variation having: content, approach_description";

        return $this->generate_content($prompt, array(
            'max_tokens' => 4096,
            'system' => 'You are a conversion optimization expert who creates effective content variations. Always respond with valid JSON.'
        ));
    }

    /**
     * Research keywords
     *
     * @param string $topic
     * @param string $industry
     * @return array
     */
    public function research_keywords($topic, $industry = '') {
        $industry_context = $industry ? " in the {$industry} industry" : '';

        $prompt = "Research and suggest SEO keywords for: {$topic}{$industry_context}

Provide:
1. Primary keywords (high volume, high competition)
2. Secondary keywords (medium volume, medium competition)
3. Long-tail keywords (lower volume, lower competition, high intent)
4. Question-based keywords (what, how, why, etc.)
5. LSI (Latent Semantic Indexing) keywords

Format as JSON with arrays for each category.";

        return $this->generate_content($prompt, array(
            'max_tokens' => 2048,
            'system' => 'You are an SEO keyword research expert. Always respond with valid JSON.'
        ));
    }

    /**
     * Generate meme text
     *
     * @param string $topic
     * @param string $template_name
     * @return array
     */
    public function generate_meme_text($topic, $template_name = '') {
        $template_context = $template_name ? " using the '{$template_name}' meme template" : '';

        $prompt = "Create funny and engaging meme text about: {$topic}{$template_context}

Provide:
1. Top text (if applicable)
2. Bottom text (if applicable)
3. Alternative versions (3 variations)

Make it relatable, funny, and shareable. Format as JSON.";

        return $this->generate_content($prompt, array(
            'max_tokens' => 512,
            'system' => 'You are a meme expert who understands internet culture and creates viral content. Always respond with valid JSON.'
        ));
    }
}
