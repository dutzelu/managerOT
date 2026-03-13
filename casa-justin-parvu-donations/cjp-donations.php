<?php
/**
 * Plugin Name: Casa Justin Parvu Donations
 * Description: Pagina moderna de donatii pentru Casa Justin Parvu, cu Stripe Payment Links si webhook-uri Stripe.
 * Version: 1.0.0
 * Author: Casa Justin Parvu
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CJP_DONATIONS_VERSION', '1.0.0');
define('CJP_DONATIONS_FILE', __FILE__);
define('CJP_DONATIONS_PATH', plugin_dir_path(__FILE__));
define('CJP_DONATIONS_URL', plugin_dir_url(__FILE__));

require_once CJP_DONATIONS_PATH . 'includes/class-cjp-activator.php';
require_once CJP_DONATIONS_PATH . 'includes/class-cjp-admin.php';
require_once CJP_DONATIONS_PATH . 'includes/class-cjp-stats.php';
require_once CJP_DONATIONS_PATH . 'includes/class-cjp-rest.php';
require_once CJP_DONATIONS_PATH . 'frontend/class-cjp-shortcode.php';

register_activation_hook(__FILE__, ['CJP_Activator', 'activate']);

add_action('plugins_loaded', function () {
    CJP_Admin::init();
    CJP_Stats::init();
    CJP_REST::init();
    CJP_Shortcode::init();
});
