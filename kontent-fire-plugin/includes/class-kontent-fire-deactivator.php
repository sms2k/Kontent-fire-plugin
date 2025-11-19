<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes
 */

class Kontent_Fire_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Cleans up scheduled events and temporary files.
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('kontent_fire_process_queue');
        wp_clear_scheduled_hook('kontent_fire_update_analytics');
        wp_clear_scheduled_hook('kontent_fire_refresh_tokens');
        wp_clear_scheduled_hook('kontent_fire_cleanup_temp');

        // Flush rewrite rules
        flush_rewrite_rules();

        // Clean up temporary files
        self::cleanup_temp_files();
    }

    /**
     * Clean up temporary files.
     */
    private static function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/kontent-fire/temp';

        if (file_exists($temp_dir)) {
            $files = glob($temp_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
