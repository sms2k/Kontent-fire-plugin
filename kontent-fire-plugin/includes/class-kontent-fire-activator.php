<?php
/**
 * Fired during plugin activation.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes
 */

class Kontent_Fire_Activator {

    /**
     * Activate the plugin.
     *
     * Creates database tables and sets up default options.
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::create_upload_directories();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables for the plugin.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Posts queue table
        $table_posts = $wpdb->prefix . 'kf_posts';
        $sql_posts = "CREATE TABLE IF NOT EXISTS $table_posts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            platform varchar(50) NOT NULL,
            content longtext NOT NULL,
            media_url text,
            media_type varchar(20),
            status varchar(20) DEFAULT 'draft',
            scheduled_time datetime,
            posted_time datetime,
            platform_post_id varchar(255),
            response longtext,
            engagement_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY platform (platform),
            KEY status (status),
            KEY scheduled_time (scheduled_time)
        ) $charset_collate;";

        // Platform connections table
        $table_platforms = $wpdb->prefix . 'kf_platform_connections';
        $sql_platforms = "CREATE TABLE IF NOT EXISTS $table_platforms (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            platform varchar(50) NOT NULL,
            account_name varchar(255),
            access_token text,
            refresh_token text,
            token_expires datetime,
            platform_user_id varchar(255),
            settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY platform (platform)
        ) $charset_collate;";

        // Content templates table
        $table_templates = $wpdb->prefix . 'kf_templates';
        $sql_templates = "CREATE TABLE IF NOT EXISTS $table_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            platforms text,
            template_data longtext NOT NULL,
            is_public tinyint(1) DEFAULT 0,
            downloads int(11) DEFAULT 0,
            rating decimal(3,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type)
        ) $charset_collate;";

        // Analytics table
        $table_analytics = $wpdb->prefix . 'kf_analytics';
        $sql_analytics = "CREATE TABLE IF NOT EXISTS $table_analytics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            platform varchar(50) NOT NULL,
            metric_name varchar(100) NOT NULL,
            metric_value bigint(20) DEFAULT 0,
            recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY platform (platform),
            KEY metric_name (metric_name),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";

        // License keys table
        $table_licenses = $wpdb->prefix . 'kf_licenses';
        $sql_licenses = "CREATE TABLE IF NOT EXISTS $table_licenses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL UNIQUE,
            user_id bigint(20),
            plan_type varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            activations_used int(11) DEFAULT 0,
            max_activations int(11) DEFAULT 1,
            expires_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Content pipeline table
        $table_pipeline = $wpdb->prefix . 'kf_content_pipeline';
        $sql_pipeline = "CREATE TABLE IF NOT EXISTS $table_pipeline (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            source_url text,
            source_type varchar(50),
            content_data longtext,
            ai_analysis longtext,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // SEO data table
        $table_seo = $wpdb->prefix . 'kf_seo_data';
        $sql_seo = "CREATE TABLE IF NOT EXISTS $table_seo (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            keywords text,
            meta_title varchar(255),
            meta_description text,
            seo_score int(11) DEFAULT 0,
            readability_score int(11) DEFAULT 0,
            suggestions longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_posts);
        dbDelta($sql_platforms);
        dbDelta($sql_templates);
        dbDelta($sql_analytics);
        dbDelta($sql_licenses);
        dbDelta($sql_pipeline);
        dbDelta($sql_seo);

        // Store database version
        add_option('kontent_fire_db_version', '1.0.0');
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = array(
            'kontent_fire_license_key' => '',
            'kontent_fire_license_status' => 'inactive',
            'kontent_fire_claude_api_key' => '',
            'kontent_fire_openai_api_key' => '',
            'kontent_fire_gemini_api_key' => '',
            'kontent_fire_google_project_id' => '',
            'kontent_fire_default_tone' => 'professional',
            'kontent_fire_auto_post' => 'no',
            'kontent_fire_seo_enabled' => 'yes',
            'kontent_fire_analytics_enabled' => 'yes',
            'kontent_fire_auto_promote_blogs' => 'yes',
            'kontent_fire_auto_promo_platforms' => json_encode(array('facebook', 'twitter', 'linkedin')),
            'kontent_fire_promo_delay_hours' => '2',
            'kontent_fire_default_image_api' => 'gemini',  // Imagen 4 by default
            'kontent_fire_default_video_api' => 'veo3',    // Veo 3 by default

            // Auto-blogging settings
            'kontent_fire_auto_blog_enabled' => 'no',
            'kontent_fire_auto_blog_frequency' => 'weekly',
            'kontent_fire_auto_blog_status' => 'publish',  // publish or draft
            'kontent_fire_auto_blog_length' => 'long',  // short (800-1500) or long (1500-2500)
            'kontent_fire_business_info' => '',
            'kontent_fire_industry' => '',
            'kontent_fire_target_zip_codes' => '',  // Comma-separated zip codes for local SEO
            'kontent_fire_blog_images_per_post' => '3',
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create upload directories for media files.
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $kontent_fire_dir = $upload_dir['basedir'] . '/kontent-fire';

        $dirs = array(
            $kontent_fire_dir,
            $kontent_fire_dir . '/images',
            $kontent_fire_dir . '/videos',
            $kontent_fire_dir . '/temp',
        );

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                // Add index.php for security
                file_put_contents($dir . '/index.php', '<?php // Silence is golden');
            }
        }
    }
}
