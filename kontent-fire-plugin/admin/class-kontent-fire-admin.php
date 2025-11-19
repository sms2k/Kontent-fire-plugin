<?php
/**
 * Admin functionality
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/admin
 */

class Kontent_Fire_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, KONTENT_FIRE_PLUGIN_URL . 'admin/css/kontent-fire-admin.css', array(), $this->version);
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, KONTENT_FIRE_PLUGIN_URL . 'admin/js/kontent-fire-admin.js', array('jquery'), $this->version, true);

        wp_localize_script($this->plugin_name, 'kontentFireAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kontent_fire_nonce')
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Kontent Fire',
            'Kontent Fire',
            'manage_options',
            'kontent-fire',
            array($this, 'display_dashboard'),
            'dashicons-fire',
            30
        );

        add_submenu_page('kontent-fire', 'Dashboard', 'Dashboard', 'manage_options', 'kontent-fire', array($this, 'display_dashboard'));
        add_submenu_page('kontent-fire', 'Auto-Blog Generator', '🔥 Auto-Blog', 'manage_options', 'kontent-fire-auto-blog', array($this, 'display_auto_blog'));
        add_submenu_page('kontent-fire', 'Content Generator', 'Generate Content', 'manage_options', 'kontent-fire-generate', array($this, 'display_generator'));
        add_submenu_page('kontent-fire', 'Schedule Posts', 'Schedule', 'manage_options', 'kontent-fire-schedule', array($this, 'display_scheduler'));
        add_submenu_page('kontent-fire', 'Media Studio', 'Media Studio', 'manage_options', 'kontent-fire-media', array($this, 'display_media_studio'));
        add_submenu_page('kontent-fire', 'Platforms', 'Platforms', 'manage_options', 'kontent-fire-platforms', array($this, 'display_platforms'));
        add_submenu_page('kontent-fire', 'Analytics', 'Analytics', 'manage_options', 'kontent-fire-analytics', array($this, 'display_analytics'));
        add_submenu_page('kontent-fire', 'Settings', 'Settings', 'manage_options', 'kontent-fire-settings', array($this, 'display_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('kontent_fire_settings', 'kontent_fire_license_key');
        register_setting('kontent_fire_settings', 'kontent_fire_claude_api_key');
        register_setting('kontent_fire_settings', 'kontent_fire_openai_api_key');
        register_setting('kontent_fire_settings', 'kontent_fire_gemini_api_key');
        register_setting('kontent_fire_settings', 'kontent_fire_default_tone');
        register_setting('kontent_fire_settings', 'kontent_fire_auto_post');

        // Auto-blog settings
        register_setting('kontent_fire_auto_blog', 'kontent_fire_auto_blog_enabled');
        register_setting('kontent_fire_auto_blog', 'kontent_fire_business_info');
        register_setting('kontent_fire_auto_blog', 'kontent_fire_industry');
        register_setting('kontent_fire_auto_blog', 'kontent_fire_target_zip_codes');
        register_setting('kontent_fire_auto_blog', 'kontent_fire_auto_blog_frequency');
        register_setting('kontent_fire_auto_blog', 'kontent_fire_auto_blog_status');
        register_setting('kontent_fire_auto_blog', 'kontent_fire_auto_blog_length');
        register_setting('kontent_fire_auto_blog', 'kontent_fire_blog_images_per_post');
    }

    /**
     * Display dashboard
     */
    public function display_dashboard() {
        $license_manager = new Kontent_Fire_License_Manager();
        $is_active = $license_manager->is_license_active();
        $queue_manager = new Kontent_Fire_Queue_Manager();
        $queue_status = $queue_manager->get_queue_status();

        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display content generator
     */
    public function display_generator() {
        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/generator.php';
    }

    /**
     * Display scheduler
     */
    public function display_scheduler() {
        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/scheduler.php';
    }

    /**
     * Display media studio
     */
    public function display_media_studio() {
        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/media-studio.php';
    }

    /**
     * Display platforms
     */
    public function display_platforms() {
        $social_manager = new Kontent_Fire_Social_API_Manager();
        $platforms = $social_manager->get_available_platforms();

        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/platforms.php';
    }

    /**
     * Display analytics
     */
    public function display_analytics() {
        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/analytics.php';
    }

    /**
     * Display settings
     */
    public function display_settings() {
        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Display auto-blog generator
     */
    public function display_auto_blog() {
        include KONTENT_FIRE_PLUGIN_DIR . 'admin/partials/auto-blog.php';
    }

    /**
     * AJAX: Generate content
     */
    public function ajax_generate_content() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $content_generator = new Kontent_Fire_Content_Generator();

        $params = array(
            'type' => sanitize_text_field($_POST['type'] ?? 'blog'),
            'topic' => sanitize_text_field($_POST['topic'] ?? ''),
            'platforms' => $_POST['platforms'] ?? array(),
            'tone' => sanitize_text_field($_POST['tone'] ?? 'professional')
        );

        $result = $content_generator->create_content($params);

        wp_send_json($result);
    }

    /**
     * AJAX: Validate license
     */
    public function ajax_validate_license() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $license_manager = new Kontent_Fire_License_Manager();
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');

        $result = $license_manager->activate_license($license_key);

        wp_send_json($result);
    }

    /**
     * AJAX: Connect platform
     */
    public function ajax_connect_platform() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $social_manager = new Kontent_Fire_Social_API_Manager();
        $platform = sanitize_text_field($_POST['platform'] ?? '');
        $credentials = $_POST['credentials'] ?? array();

        $result = $social_manager->connect($platform, $credentials);

        wp_send_json($result);
    }

    /**
     * AJAX: Schedule post
     */
    public function ajax_schedule_post() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $scheduler = new Kontent_Fire_Post_Scheduler();

        $content = $_POST['content'] ?? '';
        $params = array(
            'platform' => sanitize_text_field($_POST['platform'] ?? ''),
            'scheduled_time' => sanitize_text_field($_POST['scheduled_time'] ?? ''),
            'media_url' => esc_url_raw($_POST['media_url'] ?? '')
        );

        $result = $scheduler->schedule($content, $params);

        wp_send_json(array('success' => !empty($result), 'post_id' => $result));
    }

    /**
     * AJAX: Analyze SEO
     */
    public function ajax_analyze_seo() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $seo_analyzer = new Kontent_Fire_SEO_Analyzer();

        $content = wp_kses_post($_POST['content'] ?? '');
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');

        $result = $seo_analyzer->analyze($content, array('target_keyword' => $keyword));

        wp_send_json(array('success' => true, 'analysis' => $result));
    }

    /**
     * AJAX: Generate image
     */
    public function ajax_generate_image() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $image_generator = new Kontent_Fire_Image_Generator();

        $prompt = sanitize_text_field($_POST['prompt'] ?? '');
        $options = array(
            'size' => sanitize_text_field($_POST['size'] ?? '1024x1024'),
            'quality' => sanitize_text_field($_POST['quality'] ?? 'standard')
        );

        $result = $image_generator->generate($prompt, $options);

        wp_send_json($result);
    }

    /**
     * AJAX: Generate video
     */
    public function ajax_generate_video() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $video_generator = new Kontent_Fire_Video_Generator();

        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $options = array(
            'duration' => sanitize_text_field($_POST['duration'] ?? '30-60 seconds'),
            'platform' => sanitize_text_field($_POST['platform'] ?? 'youtube')
        );

        $result = $video_generator->generate_script($topic, $options);

        wp_send_json($result);
    }

    /**
     * AJAX: Generate auto-blog
     */
    public function ajax_generate_auto_blog() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $auto_blogger = new Kontent_Fire_Auto_Blogger();
        $topic = sanitize_text_field($_POST['topic'] ?? '');

        $options = array();
        if (!empty($topic)) {
            $options['topic'] = $topic;
        }

        $result = $auto_blogger->generate_manual($options);

        wp_send_json($result);
    }

    /**
     * AJAX: Get OAuth URL for platform connection
     */
    public function ajax_get_oauth_url() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $platform = sanitize_text_field($_POST['platform'] ?? '');

        if (empty($platform)) {
            wp_send_json_error(array('message' => 'Platform not specified'));
        }

        $oauth_manager = new Kontent_Fire_OAuth_Manager();
        $oauth_url = $oauth_manager->get_oauth_url($platform);

        if ($oauth_url) {
            wp_send_json_success(array('oauth_url' => $oauth_url));
        } else {
            wp_send_json_error(array('message' => 'Failed to generate OAuth URL. Please check your license key.'));
        }
    }

    /**
     * AJAX: Disconnect platform
     */
    public function ajax_disconnect_platform() {
        check_ajax_referer('kontent_fire_nonce', 'nonce');

        $platform = sanitize_text_field($_POST['platform'] ?? '');

        if (empty($platform)) {
            wp_send_json_error('Platform not specified');
        }

        $oauth_manager = new Kontent_Fire_OAuth_Manager();
        $result = $oauth_manager->disconnect_platform($platform);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to disconnect platform');
        }
    }
}
