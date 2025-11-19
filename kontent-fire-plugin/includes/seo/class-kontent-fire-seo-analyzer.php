<?php
/**
 * SEO Analyzer
 *
 * Analyzes content for SEO optimization.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/seo
 */

class Kontent_Fire_SEO_Analyzer {

    /**
     * AI Engine instance
     */
    private $ai_engine;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_engine = new Kontent_Fire_AI_Engine();
    }

    /**
     * Analyze content for SEO
     *
     * @param string $content
     * @param array $options
     * @return array
     */
    public function analyze($content, $options = array()) {
        $target_keyword = $options['target_keyword'] ?? '';
        $url = $options['url'] ?? '';

        $analysis = array(
            'seo_score' => 0,
            'readability_score' => 0,
            'issues' => array(),
            'suggestions' => array(),
            'passed_checks' => array()
        );

        // Word count
        $word_count = str_word_count(strip_tags($content));
        $analysis['word_count'] = $word_count;

        if ($word_count < 300) {
            $analysis['issues'][] = 'Content is too short. Aim for at least 300 words.';
        } elseif ($word_count >= 1000) {
            $analysis['passed_checks'][] = 'Good content length (1000+ words)';
            $analysis['seo_score'] += 15;
        } else {
            $analysis['passed_checks'][] = 'Acceptable content length';
            $analysis['seo_score'] += 10;
        }

        // Keyword analysis
        if (!empty($target_keyword)) {
            $keyword_analysis = $this->analyze_keyword($content, $target_keyword);
            $analysis = array_merge($analysis, $keyword_analysis);
        }

        // Readability
        $readability = $this->calculate_readability($content);
        $analysis['readability_score'] = $readability['score'];
        $analysis['reading_level'] = $readability['level'];

        if ($readability['score'] >= 60) {
            $analysis['passed_checks'][] = 'Good readability score';
            $analysis['seo_score'] += 10;
        } else {
            $analysis['issues'][] = 'Readability could be improved. Use shorter sentences and simpler words.';
        }

        // Heading analysis
        $heading_analysis = $this->analyze_headings($content);
        if ($heading_analysis['has_h1']) {
            $analysis['passed_checks'][] = 'H1 heading found';
            $analysis['seo_score'] += 10;
        } else {
            $analysis['issues'][] = 'No H1 heading found';
        }

        if ($heading_analysis['has_h2']) {
            $analysis['passed_checks'][] = 'H2 headings found';
            $analysis['seo_score'] += 5;
        }

        // Image analysis
        $image_analysis = $this->analyze_images($content);
        $analysis['images_count'] = $image_analysis['count'];
        $analysis['images_with_alt'] = $image_analysis['with_alt'];

        if ($image_analysis['count'] > 0) {
            $analysis['seo_score'] += 5;
            if ($image_analysis['with_alt'] === $image_analysis['count']) {
                $analysis['passed_checks'][] = 'All images have alt text';
                $analysis['seo_score'] += 5;
            } else {
                $analysis['issues'][] = 'Some images missing alt text';
            }
        }

        // Internal/external links
        $link_analysis = $this->analyze_links($content);
        $analysis['internal_links'] = $link_analysis['internal'];
        $analysis['external_links'] = $link_analysis['external'];

        if ($link_analysis['internal'] > 0) {
            $analysis['passed_checks'][] = 'Internal links present';
            $analysis['seo_score'] += 5;
        }

        if ($link_analysis['external'] > 0) {
            $analysis['passed_checks'][] = 'External links present';
            $analysis['seo_score'] += 5;
        }

        // Meta description length
        if (!empty($options['meta_description'])) {
            $meta_length = strlen($options['meta_description']);
            if ($meta_length >= 150 && $meta_length <= 160) {
                $analysis['passed_checks'][] = 'Meta description is optimal length';
                $analysis['seo_score'] += 10;
            } elseif ($meta_length < 150) {
                $analysis['issues'][] = 'Meta description too short (aim for 150-160 characters)';
            } else {
                $analysis['issues'][] = 'Meta description too long (may be truncated in search results)';
            }
        } else {
            $analysis['issues'][] = 'Meta description missing';
        }

        // Use AI for advanced analysis
        if (!empty($target_keyword)) {
            $ai_analysis = $this->ai_engine->generate_content('seo', array(
                'content' => substr($content, 0, 5000), // Limit content length for API
                'keyword' => $target_keyword
            ));

            if ($ai_analysis['success']) {
                $ai_data = json_decode($ai_analysis['content'], true);
                if ($ai_data) {
                    $analysis['ai_suggestions'] = $ai_data['suggestions'] ?? array();
                    $analysis['recommended_meta_title'] = $ai_data['meta_title'] ?? '';
                    $analysis['recommended_meta_description'] = $ai_data['meta_description'] ?? '';
                    $analysis['additional_keywords'] = $ai_data['additional_keywords'] ?? array();
                }
            }
        }

        // Normalize score to 0-100
        $analysis['seo_score'] = min(100, $analysis['seo_score']);

        return $analysis;
    }

    /**
     * Analyze keyword usage
     *
     * @param string $content
     * @param string $keyword
     * @return array
     */
    private function analyze_keyword($content, $keyword) {
        $content_lower = strtolower(strip_tags($content));
        $keyword_lower = strtolower($keyword);

        $count = substr_count($content_lower, $keyword_lower);
        $word_count = str_word_count($content_lower);

        $density = $word_count > 0 ? ($count / $word_count) * 100 : 0;

        $analysis = array(
            'keyword_count' => $count,
            'keyword_density' => round($density, 2)
        );

        if ($count === 0) {
            $analysis['issues'][] = "Target keyword '{$keyword}' not found in content";
        } elseif ($density < 0.5) {
            $analysis['issues'][] = "Keyword density too low. Use '{$keyword}' more naturally.";
        } elseif ($density > 2.5) {
            $analysis['issues'][] = "Keyword density too high. Reduce usage to avoid keyword stuffing.";
        } else {
            $analysis['passed_checks'][] = "Good keyword density ({$density}%)";
            $analysis['seo_score'] = ($analysis['seo_score'] ?? 0) + 15;
        }

        // Check keyword in first paragraph
        $paragraphs = explode("\n", $content_lower);
        if (!empty($paragraphs[0]) && strpos($paragraphs[0], $keyword_lower) !== false) {
            $analysis['passed_checks'][] = 'Keyword appears in first paragraph';
            $analysis['seo_score'] = ($analysis['seo_score'] ?? 0) + 10;
        } else {
            $analysis['issues'][] = 'Keyword should appear in the first paragraph';
        }

        return $analysis;
    }

    /**
     * Calculate readability score (Flesch Reading Ease)
     *
     * @param string $content
     * @return array
     */
    private function calculate_readability($content) {
        $text = strip_tags($content);

        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);

        $words = str_word_count($text);

        $syllables = $this->count_syllables($text);

        if ($sentence_count === 0 || $words === 0) {
            return array('score' => 0, 'level' => 'N/A');
        }

        $score = 206.835 - 1.015 * ($words / $sentence_count) - 84.6 * ($syllables / $words);
        $score = max(0, min(100, $score));

        $level = 'Difficult';
        if ($score >= 90) $level = 'Very Easy';
        elseif ($score >= 80) $level = 'Easy';
        elseif ($score >= 70) $level = 'Fairly Easy';
        elseif ($score >= 60) $level = 'Standard';
        elseif ($score >= 50) $level = 'Fairly Difficult';

        return array(
            'score' => round($score, 1),
            'level' => $level
        );
    }

    /**
     * Count syllables in text
     *
     * @param string $text
     * @return int
     */
    private function count_syllables($text) {
        $words = str_word_count(strtolower($text), 1);
        $syllables = 0;

        foreach ($words as $word) {
            $syllables += $this->count_syllables_in_word($word);
        }

        return max(1, $syllables);
    }

    /**
     * Count syllables in a word
     *
     * @param string $word
     * @return int
     */
    private function count_syllables_in_word($word) {
        $word = strtolower($word);
        $count = 0;

        $vowels = array('a', 'e', 'i', 'o', 'u', 'y');
        $previous_was_vowel = false;

        for ($i = 0; $i < strlen($word); $i++) {
            $is_vowel = in_array($word[$i], $vowels);

            if ($is_vowel && !$previous_was_vowel) {
                $count++;
            }

            $previous_was_vowel = $is_vowel;
        }

        // Adjust for silent e
        if (substr($word, -1) === 'e') {
            $count--;
        }

        return max(1, $count);
    }

    /**
     * Analyze headings
     *
     * @param string $content
     * @return array
     */
    private function analyze_headings($content) {
        $has_h1 = preg_match('/<h1[^>]*>/', $content) > 0;
        $has_h2 = preg_match('/<h2[^>]*>/', $content) > 0;
        $h1_count = preg_match_all('/<h1[^>]*>/', $content);
        $h2_count = preg_match_all('/<h2[^>]*>/', $content);

        return array(
            'has_h1' => $has_h1,
            'has_h2' => $has_h2,
            'h1_count' => $h1_count,
            'h2_count' => $h2_count
        );
    }

    /**
     * Analyze images
     *
     * @param string $content
     * @return array
     */
    private function analyze_images($content) {
        preg_match_all('/<img[^>]+>/', $content, $images);
        $count = count($images[0]);

        $with_alt = 0;
        foreach ($images[0] as $img) {
            if (preg_match('/alt=["\'][^"\']*["\']/', $img)) {
                $with_alt++;
            }
        }

        return array(
            'count' => $count,
            'with_alt' => $with_alt
        );
    }

    /**
     * Analyze links
     *
     * @param string $content
     * @return array
     */
    private function analyze_links($content) {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/', $content, $links);

        $internal = 0;
        $external = 0;
        $site_url = get_site_url();

        foreach ($links[1] as $url) {
            if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                $internal++;
            } else {
                $external++;
            }
        }

        return array(
            'internal' => $internal,
            'external' => $external
        );
    }

    /**
     * Generate SEO report
     *
     * @param int $post_id
     * @return array
     */
    public function generate_report($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_posts';

        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $post_id
        ), ARRAY_A);

        if (!$post) {
            return array(
                'success' => false,
                'message' => 'Post not found'
            );
        }

        $analysis = $this->analyze($post['content']);

        // Save SEO data
        $seo_table = $wpdb->prefix . 'kf_seo_data';
        $wpdb->insert($seo_table, array(
            'post_id' => $post_id,
            'seo_score' => $analysis['seo_score'],
            'readability_score' => $analysis['readability_score'],
            'suggestions' => json_encode($analysis['suggestions'] ?? array()),
            'keywords' => json_encode($analysis['additional_keywords'] ?? array())
        ));

        return array(
            'success' => true,
            'analysis' => $analysis
        );
    }
}
