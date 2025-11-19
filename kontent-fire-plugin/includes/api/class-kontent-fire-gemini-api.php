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
     * Imagen 4 endpoint
     */
    private $imagen_endpoint = 'https://us-central1-aiplatform.googleapis.com/v1/projects/{project}/locations/us-central1/publishers/google/models/imagen-4.0:predict';

    /**
     * Veo 3 endpoint
     */
    private $veo_endpoint = 'https://us-central1-aiplatform.googleapis.com/v1/projects/{project}/locations/us-central1/publishers/google/models/veo-3.0:predict';

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
     * Generate image using Imagen 4
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
            'safetyFilterLevel' => 'block_some',
            'outputMimeType' => 'image/png',
            'personGeneration' => 'allow_adult'
        );

        $options = wp_parse_args($options, $defaults);

        // Imagen 4 uses Vertex AI endpoint
        $project = get_option('kontent_fire_google_project_id', 'your-project-id');
        $endpoint = str_replace('{project}', $project, $this->imagen_endpoint);

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
                'safetyFilterLevel' => $options['safetyFilterLevel'],
                'outputOptions' => array(
                    'mimeType' => $options['outputMimeType']
                ),
                'personGeneration' => $options['personGeneration']
            )
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 120
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Imagen 4 API request failed: ' . $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'Imagen 4 API error: ' . ($data['error']['message'] ?? 'Unknown error'),
                'code' => $response_code
            );
        }

        // Download and save images
        $image_urls = array();
        if (isset($data['predictions'])) {
            foreach ($data['predictions'] as $prediction) {
                if (isset($prediction['bytesBase64Encoded'])) {
                    $image_data = base64_decode($prediction['bytesBase64Encoded']);
                    $saved = $this->save_imagen_to_media($image_data, $prompt);
                    if ($saved) {
                        $image_urls[] = $saved;
                    }
                }
            }
        }

        return array(
            'success' => true,
            'images' => $image_urls,
            'model' => 'Imagen 4',
            'data' => $data
        );
    }

    /**
     * Edit image using Gemini 2.5 "Nana Banana"
     *
     * @param string $image_path
     * @param string $edit_prompt
     * @param array $options
     * @return array
     */
    public function edit_image_nana_banana($image_path, $edit_prompt, $options = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Gemini API key not configured.'
            );
        }

        // Read and encode image
        $image_data = file_get_contents($image_path);
        if ($image_data === false) {
            return array(
                'success' => false,
                'message' => 'Could not read image file.'
            );
        }

        $base64_image = base64_encode($image_data);
        $mime_type = mime_content_type($image_path);

        $defaults = array(
            'temperature' => 0.4,
            'maxOutputTokens' => 8192
        );

        $options = wp_parse_args($options, $defaults);

        // Use Gemini 2.5 Flash with vision for image editing
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'inline_data' => array(
                                'mime_type' => $mime_type,
                                'data' => $base64_image
                            )
                        ),
                        array('text' => "Edit this image as follows: {$edit_prompt}\n\nProvide detailed editing instructions that can be applied to transform the image.")
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => $options['temperature'],
                'maxOutputTokens' => $options['maxOutputTokens']
            )
        );

        $url = $this->api_endpoint . '/models/gemini-2.5-flash:generateContent?key=' . $this->api_key;

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 90
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Nana Banana API request failed: ' . $response->get_error_message()
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        $editing_instructions = '';
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $editing_instructions = $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return array(
            'success' => true,
            'model' => 'Gemini 2.5 Flash (Nana Banana)',
            'editing_instructions' => $editing_instructions,
            'original_prompt' => $edit_prompt,
            'data' => $data
        );
    }

    /**
     * Generate video using Veo 3
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate_video_veo3($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Gemini API key not configured.'
            );
        }

        $defaults = array(
            'duration' => '5',  // seconds
            'aspectRatio' => '16:9',
            'fps' => 24,
            'resolution' => '1080p'
        );

        $options = wp_parse_args($options, $defaults);

        // Veo 3 uses Vertex AI endpoint
        $project = get_option('kontent_fire_google_project_id', 'your-project-id');
        $endpoint = str_replace('{project}', $project, $this->veo_endpoint);

        $body = array(
            'instances' => array(
                array(
                    'prompt' => $prompt
                )
            ),
            'parameters' => array(
                'duration' => $options['duration'],
                'aspectRatio' => $options['aspectRatio'],
                'fps' => $options['fps'],
                'resolution' => $options['resolution']
            )
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 180  // 3 minutes for video generation
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Veo 3 API request failed: ' . $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'Veo 3 API error: ' . ($data['error']['message'] ?? 'Unknown error'),
                'code' => $response_code
            );
        }

        // Process video data
        $video_urls = array();
        if (isset($data['predictions'])) {
            foreach ($data['predictions'] as $prediction) {
                if (isset($prediction['bytesBase64Encoded'])) {
                    $video_data = base64_decode($prediction['bytesBase64Encoded']);
                    $saved = $this->save_video_to_media($video_data, $prompt);
                    if ($saved) {
                        $video_urls[] = $saved;
                    }
                }
            }
        }

        return array(
            'success' => true,
            'videos' => $video_urls,
            'model' => 'Veo 3',
            'data' => $data
        );
    }

    /**
     * Save Imagen image to WordPress media library (optimized as WebP)
     *
     * @param string $image_data
     * @param string $prompt
     * @return string|false
     */
    private function save_imagen_to_media($image_data, $prompt) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload_dir = wp_upload_dir();

        // First save as PNG temporarily
        $temp_filename = 'imagen4-temp-' . time() . '.png';
        $temp_filepath = $upload_dir['path'] . '/' . $temp_filename;
        file_put_contents($temp_filepath, $image_data);

        // Convert to WebP for performance optimization
        $webp_result = $this->convert_to_webp($temp_filepath, $prompt);

        // Delete temporary PNG
        @unlink($temp_filepath);

        if (!$webp_result) {
            // Fallback to PNG if WebP conversion fails
            $filename = 'imagen4-' . sanitize_title(substr($prompt, 0, 50)) . '-' . time() . '.png';
            $filepath = $upload_dir['path'] . '/' . $filename;
            file_put_contents($filepath, $image_data);
            $mime_type = 'image/png';
        } else {
            $filepath = $webp_result['filepath'];
            $filename = $webp_result['filename'];
            $mime_type = 'image/webp';
        }

        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $mime_type,
            'post_title' => 'Imagen 4: ' . substr($prompt, 0, 100),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $filepath);
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return wp_get_attachment_url($attach_id);
    }

    /**
     * Convert image to WebP format for performance
     *
     * @param string $source_path Source image path
     * @param string $prompt Image prompt for naming
     * @return array|false Array with filepath and filename, or false on failure
     */
    private function convert_to_webp($source_path, $prompt) {
        // Check if GD or Imagick supports WebP
        if (!function_exists('imagewebp') && !extension_loaded('imagick')) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $filename = 'imagen4-' . sanitize_title(substr($prompt, 0, 50)) . '-' . time() . '.webp';
        $output_path = $upload_dir['path'] . '/' . $filename;

        // Try GD first (faster)
        if (function_exists('imagewebp') && function_exists('imagecreatefrompng')) {
            $image = @imagecreatefrompng($source_path);

            if ($image !== false) {
                // Enable alpha channel for transparency
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);

                // Convert to WebP with 85% quality (good balance)
                $success = imagewebp($image, $output_path, 85);
                imagedestroy($image);

                if ($success) {
                    return array(
                        'filepath' => $output_path,
                        'filename' => $filename
                    );
                }
            }
        }

        // Try Imagick as fallback
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick($source_path);
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality(85);
                $imagick->stripImage(); // Remove metadata for smaller file size
                $imagick->writeImage($output_path);
                $imagick->clear();
                $imagick->destroy();

                return array(
                    'filepath' => $output_path,
                    'filename' => $filename
                );
            } catch (Exception $e) {
                error_log('Kontent Fire: WebP conversion failed - ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Save Veo 3 video to WordPress media library
     *
     * @param string $video_data
     * @param string $prompt
     * @return string|false
     */
    private function save_video_to_media($video_data, $prompt) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload_dir = wp_upload_dir();
        $filename = 'veo3-' . sanitize_title(substr($prompt, 0, 50)) . '-' . time() . '.mp4';
        $filepath = $upload_dir['path'] . '/' . $filename;

        file_put_contents($filepath, $video_data);

        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'video/mp4',
            'post_title' => 'Veo 3: ' . substr($prompt, 0, 100),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $filepath);

        return wp_get_attachment_url($attach_id);
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
