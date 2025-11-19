<?php
/**
 * Public-facing functionality
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/public
 */

class Kontent_Fire_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, KONTENT_FIRE_PLUGIN_URL . 'public/css/kontent-fire-public.css', array(), $this->version);
    }

    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, KONTENT_FIRE_PLUGIN_URL . 'public/js/kontent-fire-public.js', array('jquery'), $this->version, true);
    }
}
