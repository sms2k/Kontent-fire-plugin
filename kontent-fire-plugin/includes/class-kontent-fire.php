<?php
/**
 * The core plugin class.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes
 */

class Kontent_Fire {

    /**
     * The loader responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Initialize the plugin.
     */
    public function __construct() {
        $this->version = KONTENT_FIRE_VERSION;
        $this->plugin_name = 'kontent-fire';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->setup_cron_jobs();

        // Initialize blog promoter after WordPress is fully loaded
        add_action('init', array($this, 'init_blog_promoter'));
    }

    /**
     * Initialize blog promoter for automatic social media promotion
     *
     * This runs on the 'init' hook after WordPress is fully loaded
     */
    public function init_blog_promoter() {
        // Only run on admin or cron
        if (!is_admin() && !wp_doing_cron()) {
            return;
        }

        // Only initialize if classes are available
        if (!class_exists('Kontent_Fire_Blog_Promoter') || !class_exists('Kontent_Fire_Auto_Blogger')) {
            return;
        }

        try {
            // Initialize blog promoter (sets up hooks for auto-promotion)
            new Kontent_Fire_Blog_Promoter();

            // Schedule automatic blog generation if enabled
            if (get_option('kontent_fire_auto_blog_enabled', 'no') === 'yes') {
                $auto_blogger = new Kontent_Fire_Auto_Blogger();
                $frequency = get_option('kontent_fire_auto_blog_frequency', 'weekly');
                if (method_exists($auto_blogger, 'schedule_auto_generation')) {
                    $auto_blogger->schedule_auto_generation($frequency);
                }
            }
        } catch (Exception $e) {
            // Log error but don't break plugin activation
            error_log('Kontent Fire: Error initializing blog features - ' . $e->getMessage());
        }
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core classes
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/class-kontent-fire-loader.php';

        // Licensing
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/licensing/class-kontent-fire-license-manager.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/licensing/class-kontent-fire-credit-manager.php';

        // OAuth
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/class-kontent-fire-oauth-manager.php';

        // API integrations
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/api/class-kontent-fire-api-proxy.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/api/class-kontent-fire-claude-api.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/api/class-kontent-fire-openai-api.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/api/class-kontent-fire-gemini-api.php';

        // Content generation
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/content-generation/class-kontent-fire-content-generator.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/content-generation/class-kontent-fire-ai-engine.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/content-generation/class-kontent-fire-blog-promoter.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/content-generation/class-kontent-fire-auto-blogger.php';

        // SEO
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/seo/class-kontent-fire-seo-analyzer.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/seo/class-kontent-fire-keyword-research.php';

        // Social platforms
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/social-platforms/class-kontent-fire-social-api-manager.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/social-platforms/class-kontent-fire-facebook-handler.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/social-platforms/class-kontent-fire-instagram-handler.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/social-platforms/class-kontent-fire-twitter-handler.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/social-platforms/class-kontent-fire-linkedin-handler.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/social-platforms/class-kontent-fire-tiktok-handler.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/social-platforms/class-kontent-fire-youtube-handler.php';

        // Media generation
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/media/class-kontent-fire-image-generator.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/media/class-kontent-fire-video-generator.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/media/class-kontent-fire-meme-studio.php';

        // Scheduler
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/scheduler/class-kontent-fire-post-scheduler.php';
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/scheduler/class-kontent-fire-queue-manager.php';

        // Database
        require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/database/class-kontent-fire-db.php';

        // Admin
        require_once KONTENT_FIRE_PLUGIN_DIR . 'admin/class-kontent-fire-admin.php';

        // Public
        require_once KONTENT_FIRE_PLUGIN_DIR . 'public/class-kontent-fire-public.php';

        $this->loader = new Kontent_Fire_Loader();
    }

    /**
     * Register all hooks related to the admin area.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Kontent_Fire_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // AJAX handlers
        $this->loader->add_action('wp_ajax_kf_generate_content', $plugin_admin, 'ajax_generate_content');
        $this->loader->add_action('wp_ajax_kf_validate_license', $plugin_admin, 'ajax_validate_license');
        $this->loader->add_action('wp_ajax_kf_connect_platform', $plugin_admin, 'ajax_connect_platform');
        $this->loader->add_action('wp_ajax_kf_schedule_post', $plugin_admin, 'ajax_schedule_post');
        $this->loader->add_action('wp_ajax_kf_analyze_seo', $plugin_admin, 'ajax_analyze_seo');
        $this->loader->add_action('wp_ajax_kf_generate_image', $plugin_admin, 'ajax_generate_image');
        $this->loader->add_action('wp_ajax_kf_generate_video', $plugin_admin, 'ajax_generate_video');
        $this->loader->add_action('wp_ajax_kf_generate_auto_blog', $plugin_admin, 'ajax_generate_auto_blog');
        $this->loader->add_action('wp_ajax_kf_get_oauth_url', $plugin_admin, 'ajax_get_oauth_url');
        $this->loader->add_action('wp_ajax_kf_disconnect_platform', $plugin_admin, 'ajax_disconnect_platform');
    }

    /**
     * Register all hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new Kontent_Fire_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Set up WordPress cron jobs.
     */
    private function setup_cron_jobs() {
        if (!wp_next_scheduled('kontent_fire_process_queue')) {
            wp_schedule_event(time(), 'every_five_minutes', 'kontent_fire_process_queue');
        }

        if (!wp_next_scheduled('kontent_fire_update_analytics')) {
            wp_schedule_event(time(), 'hourly', 'kontent_fire_update_analytics');
        }

        if (!wp_next_scheduled('kontent_fire_refresh_tokens')) {
            wp_schedule_event(time(), 'daily', 'kontent_fire_refresh_tokens');
        }

        if (!wp_next_scheduled('kontent_fire_cleanup_temp')) {
            wp_schedule_event(time(), 'daily', 'kontent_fire_cleanup_temp');
        }

        // Add custom cron intervals
        add_filter('cron_schedules', array($this, 'custom_cron_intervals'));

        // Register cron action hooks
        add_action('kontent_fire_refresh_tokens', array($this, 'refresh_oauth_tokens'));
    }

    /**
     * Refresh OAuth tokens for connected platforms
     */
    public function refresh_oauth_tokens() {
        if (class_exists('Kontent_Fire_OAuth_Manager')) {
            $oauth_manager = new Kontent_Fire_OAuth_Manager();
            $oauth_manager->refresh_expired_tokens();
        }
    }

    /**
     * Add custom cron intervals.
     */
    public function custom_cron_intervals($schedules) {
        $schedules['every_five_minutes'] = array(
            'interval' => 300,
            'display'  => __('Every 5 Minutes', 'kontent-fire')
        );
        return $schedules;
    }

    /**
     * Run the loader to execute all hooks.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number.
     */
    public function get_version() {
        return $this->version;
    }
}
