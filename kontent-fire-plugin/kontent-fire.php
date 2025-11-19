<?php
/**
 * Plugin Name: Kontent Fire by Kynex
 * Plugin URI: https://kynex.io/kontent-fire
 * Description: The ultimate AI-powered content creation and multi-platform publishing solution. Automates blog posts, social media content, images, and videos using Claude, OpenAI, and Gemini APIs with advanced SEO optimization.
 * Version: 1.0.0
 * Author: Kynex
 * Author URI: https://kynex.io
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: kontent-fire
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('KONTENT_FIRE_VERSION', '1.0.0');
define('KONTENT_FIRE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KONTENT_FIRE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KONTENT_FIRE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_kontent_fire() {
    require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/class-kontent-fire-activator.php';
    Kontent_Fire_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_kontent_fire() {
    require_once KONTENT_FIRE_PLUGIN_DIR . 'includes/class-kontent-fire-deactivator.php';
    Kontent_Fire_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_kontent_fire');
register_deactivation_hook(__FILE__, 'deactivate_kontent_fire');

/**
 * The core plugin class.
 */
require KONTENT_FIRE_PLUGIN_DIR . 'includes/class-kontent-fire.php';

/**
 * Begins execution of the plugin.
 */
function run_kontent_fire() {
    $plugin = new Kontent_Fire();
    $plugin->run();
}
run_kontent_fire();
