<?php
/**
 * Automatic Blog Generator
 *
 * Automatically creates SEO-optimized blogs with AI research, LSI keywords, and local targeting.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/content-generation
 */

class Kontent_Fire_Auto_Blogger {

    /**
     * AI Engine instance
     */
    private $ai_engine;

    /**
     * Claude API instance
     */
    private $claude_api;

    /**
     * SEO Analyzer instance
     */
    private $seo_analyzer;

    /**
     * Keyword Research instance
     */
    private $keyword_research;

    /**
     * Image Generator instance
     */
    private $image_generator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_engine = new Kontent_Fire_AI_Engine();
        $this->claude_api = new Kontent_Fire_Claude_API();
        $this->seo_analyzer = new Kontent_Fire_SEO_Analyzer();
        $this->keyword_research = new Kontent_Fire_Keyword_Research();
        $this->image_generator = new Kontent_Fire_Image_Generator();

        // Hook for automatic blog generation
        add_action('kontent_fire_generate_auto_blog', array($this, 'generate_blog'));
    }

    /**
     * Generate automatic blog with full AI research
     *
     * @param array $options
     * @return array
     */
    public function generate_blog($options = array()) {
        // Get settings
        $business_info = get_option('kontent_fire_business_info', '');
        $industry = get_option('kontent_fire_industry', '');
        $target_zips = get_option('kontent_fire_target_zip_codes', array());

        if (is_string($target_zips)) {
            $target_zips = array_filter(array_map('trim', explode(',', $target_zips)));
        }

        // Determine if local or national focus
        $is_local = !empty($target_zips);

        // Step 1: Deep AI Research - Find trending topics
        $topic_result = $this->research_blog_topics($business_info, $industry, $is_local);

        if (!$topic_result['success']) {
            return $topic_result;
        }

        $topics = json_decode($topic_result['content'], true);
        $selected_topic = $topics[0] ?? array('topic' => $business_info, 'angle' => 'general');

        // Step 2: LSI Keyword Research
        $keyword_result = $this->research_lsi_keywords($selected_topic['topic'], $industry);

        if (!$keyword_result['success']) {
            return $keyword_result;
        }

        $keywords_data = json_decode($keyword_result['content'], true);
        $primary_keywords = $keywords_data['primary'] ?? array();
        $lsi_keywords = $keywords_data['lsi'] ?? array();
        $long_tail_keywords = $keywords_data['long_tail'] ?? array();

        // Step 3: Local Research (if applicable)
        $local_data = null;
        if ($is_local && !empty($target_zips)) {
            $local_data = $this->research_local_areas($target_zips[0]);
        }

        // Step 4: Generate SEO-Optimized Blog with Claude
        $blog_result = $this->create_seo_blog(
            $selected_topic,
            $primary_keywords,
            $lsi_keywords,
            $long_tail_keywords,
            $local_data,
            $business_info,
            $industry
        );

        if (!$blog_result['success']) {
            return $blog_result;
        }

        $blog_data = json_decode($blog_result['content'], true);

        // Step 5: Generate Multiple Images (2-3 per blog)
        $images = $this->generate_blog_images($blog_data['title'], $selected_topic['topic'], 3);

        // Step 6: Create WordPress Post
        $post_id = $this->create_wordpress_post($blog_data, $images, $primary_keywords);

        // Step 7: Auto-promotion will happen automatically via Blog Promoter

        return array(
            'success' => true,
            'post_id' => $post_id,
            'title' => $blog_data['title'],
            'topic' => $selected_topic['topic'],
            'keywords' => $primary_keywords,
            'images_count' => count($images),
            'local_targeting' => $is_local
        );
    }

    /**
     * Research blog topics using AI
     *
     * @param string $business_info
     * @param string $industry
     * @param bool $is_local
     * @return array
     */
    private function research_blog_topics($business_info, $industry, $is_local) {
        $local_context = $is_local ? 'Focus on local SEO and community-relevant topics.' : 'Focus on national-level thought leadership and industry trends.';

        $prompt = "Deep research: Find 5 excellent blog topics for this business.

Business: {$business_info}
Industry: {$industry}
Scope: " . ($is_local ? 'Local SEO' : 'National SEO') . "

{$local_context}

Requirements:
1. Topics should be highly relevant to the business
2. High search volume potential
3. Low to medium competition
4. Evergreen or trending
5. Provides real value to readers
6. Strong SEO opportunity

For each topic provide:
- topic: The main topic
- angle: Unique angle to take
- seo_potential: (high/medium/low)
- target_audience: Who would read this
- why_now: Why this topic is relevant now

Format as JSON array of topics.";

        return $this->claude_api->generate_content($prompt, array(
            'max_tokens' => 3000,
            'temperature' => 0.8,
            'system' => 'You are an expert SEO strategist and content researcher who identifies high-value blog topics. Always respond with valid JSON.'
        ));
    }

    /**
     * Research LSI keywords
     *
     * @param string $topic
     * @param string $industry
     * @return array
     */
    private function research_lsi_keywords($topic, $industry) {
        $prompt = "LSI Keyword Research for: {$topic} (Industry: {$industry})

Provide comprehensive keyword research:

1. Primary Keywords (5-7):
   - Main target keywords
   - High volume, relevant
   - Include search intent

2. LSI Keywords (15-20):
   - Latent Semantic Indexing keywords
   - Related terms that search engines expect
   - Natural variations
   - Synonyms and related concepts

3. Long-tail Keywords (10-15):
   - 3-5 word phrases
   - Lower competition
   - High conversion intent
   - Question-based

4. Related Entities (10):
   - People, places, things related to topic
   - Companies, brands, locations
   - Concepts and terms

Format as JSON:
{
  \"primary\": [\"keyword1\", \"keyword2\", ...],
  \"lsi\": [\"lsi1\", \"lsi2\", ...],
  \"long_tail\": [\"long phrase 1\", ...],
  \"entities\": [\"entity1\", \"entity2\", ...]
}";

        return $this->claude_api->generate_content($prompt, array(
            'max_tokens' => 2500,
            'temperature' => 0.6,
            'system' => 'You are an expert SEO keyword researcher with deep understanding of search engine algorithms and LSI. Always respond with valid JSON.'
        ));
    }

    /**
     * Research local areas based on zip code
     *
     * @param string $zip_code
     * @return array|null
     */
    private function research_local_areas($zip_code) {
        $prompt = "Local SEO Research for ZIP code: {$zip_code}

Provide detailed information about this area:

1. City/Town name
2. County
3. State
4. Major neighborhoods (5-10)
5. Business districts/parks (3-5)
6. Shopping centers/malls (3-5)
7. Landmarks and points of interest (5-10)
8. Local characteristics (demographics, economy, culture)
9. Nearby cities (within 20 miles)
10. Local search terms people use

Format as JSON:
{
  \"city\": \"\",
  \"county\": \"\",
  \"state\": \"\",
  \"neighborhoods\": [\"\", \"\"],
  \"business_areas\": [\"\", \"\"],
  \"shopping_centers\": [\"\", \"\"],
  \"landmarks\": [\"\", \"\"],
  \"characteristics\": \"\",
  \"nearby_cities\": [\"\", \"\"],
  \"local_terms\": [\"\", \"\"]
}";

        $result = $this->claude_api->generate_content($prompt, array(
            'max_tokens' => 2000,
            'temperature' => 0.5,
            'system' => 'You are a local SEO expert with knowledge of US geography and local business markets. Always respond with valid JSON.'
        ));

        if ($result['success']) {
            return json_decode($result['content'], true);
        }

        return null;
    }

    /**
     * Create SEO-optimized blog with Claude
     *
     * @param array $topic
     * @param array $primary_keywords
     * @param array $lsi_keywords
     * @param array $long_tail_keywords
     * @param array|null $local_data
     * @param string $business_info
     * @param string $industry
     * @return array
     */
    private function create_seo_blog($topic, $primary_keywords, $lsi_keywords, $long_tail_keywords, $local_data, $business_info, $industry) {
        // Get blog length preference
        $blog_length = get_option('kontent_fire_auto_blog_length', 'long');
        $word_count = ($blog_length === 'short') ? '800-1500 words' : '1500-2500 words';
        $local_context = '';
        if ($local_data) {
            $local_context = "
LOCAL TARGETING - VERY IMPORTANT:
This blog must be hyper-targeted to {$local_data['city']}, {$local_data['state']}

Naturally mention these local elements throughout the content:
- Neighborhoods: " . implode(', ', array_slice($local_data['neighborhoods'], 0, 5)) . "
- Business areas: " . implode(', ', array_slice($local_data['business_areas'], 0, 3)) . "
- Local landmarks: " . implode(', ', array_slice($local_data['landmarks'], 0, 5)) . "

Examples:
- \"Many businesses in [neighborhood] are discovering...\"
- \"From [shopping center] to [business park], companies are...\"
- \"Whether you're near [landmark] or across town in [neighborhood]...\"

Make the local references feel NATURAL and HELPFUL, not forced.";
        }

        $prompt = "Create an AMAZING, SEO-optimized blog post.

Topic: {$topic['topic']}
Angle: {$topic['angle']}
Business: {$business_info}
Industry: {$industry}

PRIMARY KEYWORDS (use naturally throughout):
" . implode(', ', array_slice($primary_keywords, 0, 5)) . "

LSI KEYWORDS (sprinkle throughout):
" . implode(', ', array_slice($lsi_keywords, 0, 15)) . "

LONG-TAIL KEYWORDS (use in subheadings and naturally):
" . implode(', ', array_slice($long_tail_keywords, 0, 10)) . "

{$local_context}

REQUIREMENTS:
✅ {$word_count} (comprehensive, valuable)
✅ Engaging, conversational tone
✅ Perfect keyword placement (natural, not stuffed)
✅ Use ALL primary keywords at least 2-3 times
✅ Integrate LSI keywords naturally throughout
✅ H2 and H3 subheadings with keywords
✅ Include: Introduction, 5-7 main sections, Conclusion
✅ Add expert insights and data
✅ Strong call-to-action at end
✅ Internal linking opportunities (mark with [LINK:anchor text])
✅ Make it genuinely helpful and engaging

FORMAT AS JSON:
{
  \"title\": \"SEO-optimized title with primary keyword\",
  \"meta_description\": \"155-160 characters with primary keyword and CTA\",
  \"introduction\": \"Hook and overview paragraph\",
  \"sections\": [
    {
      \"heading\": \"H2 heading with keyword\",
      \"content\": \"Section content with LSI keywords\"
    }
  ],
  \"conclusion\": \"Summary with CTA\",
  \"slug\": \"url-friendly-slug\",
  \"focus_keyword\": \"primary keyword\",
  \"tags\": [\"tag1\", \"tag2\", \"tag3\"]
}";

        return $this->claude_api->generate_content($prompt, array(
            'max_tokens' => 8000,
            'temperature' => 0.7,
            'system' => 'You are the world\'s best SEO blog writer. You create engaging, helpful content that ranks #1 on Google while providing real value to readers. You master the art of natural keyword integration. Always respond with valid JSON.'
        ));
    }

    /**
     * Generate multiple images for blog
     *
     * @param string $title
     * @param string $topic
     * @param int $count
     * @return array
     */
    private function generate_blog_images($title, $topic, $count = 3) {
        $images = array();

        // Generate different image prompts
        $image_prompts = array(
            "Professional featured image for blog post: {$title}. High-quality, engaging, modern design.",
            "Infographic-style image illustrating: {$topic}. Clean, professional, informative.",
            "Supporting visual for article about {$topic}. Relevant, high-quality, professional."
        );

        for ($i = 0; $i < min($count, 3); $i++) {
            $result = $this->image_generator->generate($image_prompts[$i], array(
                'api' => 'gemini',  // Use Imagen 4
                'aspectRatio' => '16:9',
                'quality' => 'high'
            ));

            if ($result['success'] && !empty($result['images'])) {
                $images[] = $result['images'][0];
            }

            // Small delay to avoid rate limiting
            sleep(2);
        }

        return $images;
    }

    /**
     * Create WordPress post
     *
     * @param array $blog_data
     * @param array $images
     * @param array $keywords
     * @return int|false
     */
    private function create_wordpress_post($blog_data, $images, $keywords) {
        // Build full content
        $content = '<p>' . $blog_data['introduction'] . '</p>';

        $image_index = 0;
        foreach ($blog_data['sections'] as $index => $section) {
            $content .= '<h2>' . $section['heading'] . '</h2>';
            $content .= '<p>' . $section['content'] . '</p>';

            // Insert image after first and third sections
            if (($index === 0 || $index === 2) && isset($images[$image_index])) {
                $content .= '<img src="' . esc_url($images[$image_index]) . '" alt="' . esc_attr($section['heading']) . '" class="aligncenter" />';
                $image_index++;
            }
        }

        $content .= '<h2>Conclusion</h2>';
        $content .= '<p>' . $blog_data['conclusion'] . '</p>';

        // Create post
        $post_data = array(
            'post_title' => $blog_data['title'],
            'post_content' => $content,
            'post_status' => get_option('kontent_fire_auto_blog_status', 'publish'),
            'post_type' => 'post',
            'post_excerpt' => $blog_data['meta_description'],
            'post_name' => $blog_data['slug'],
            'tags_input' => $blog_data['tags'],
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // Set featured image
            if (!empty($images[0])) {
                $attachment_id = attachment_url_to_postid($images[0]);
                if ($attachment_id) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }

            // Store SEO metadata
            update_post_meta($post_id, '_kf_focus_keyword', $blog_data['focus_keyword']);
            update_post_meta($post_id, '_kf_keywords', json_encode($keywords));
            update_post_meta($post_id, '_kf_auto_generated', true);
            update_post_meta($post_id, '_kf_generation_time', current_time('mysql'));

            // Update Yoast/Rank Math meta if available
            update_post_meta($post_id, '_yoast_wpseo_title', $blog_data['title']);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $blog_data['meta_description']);
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $blog_data['focus_keyword']);
        }

        return $post_id;
    }

    /**
     * Schedule automatic blog generation
     *
     * @param string $frequency How often to generate (daily, weekly, etc.)
     */
    public function schedule_auto_generation($frequency = 'weekly') {
        $schedules = array(
            'daily' => DAY_IN_SECONDS,
            'twice_weekly' => 3 * DAY_IN_SECONDS,
            'weekly' => WEEK_IN_SECONDS,
            'monthly' => MONTH_IN_SECONDS
        );

        $interval = $schedules[$frequency] ?? WEEK_IN_SECONDS;

        if (!wp_next_scheduled('kontent_fire_generate_auto_blog')) {
            wp_schedule_event(time(), $frequency, 'kontent_fire_generate_auto_blog');
        }
    }

    /**
     * Manual blog generation
     *
     * @param string $topic Optional specific topic
     * @return array
     */
    public function generate_manual($topic = null) {
        $options = array();
        if ($topic) {
            $options['topic'] = $topic;
        }

        return $this->generate_blog($options);
    }
}
