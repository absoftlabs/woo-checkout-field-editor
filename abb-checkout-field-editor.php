<?php
/*
Plugin Name: Checkout Field Editor (Bangladesh Ready) for WooCommerce
Plugin URI: https://absoftlab.com/product/checkout-field-editor-bd-for-woocommerce/
Description: Flexible checkout field editor for WooCommerce with Bangladesh district & sub-district dropdowns.
Version: 1.3.0
Author: absoftlab
Author URI: https://absoftlab.com
Text Domain: abb-checkout-field-editor-bd
Domain Path: /languages
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/



if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ABB_WCFE_BD_VER', '1.3.0' );
define( 'ABB_WCFE_BD_FILE', __FILE__ );
define( 'ABB_WCFE_BD_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABB_WCFE_BD_URL', plugin_dir_url( __FILE__ ) );

require_once ABB_WCFE_BD_DIR . 'includes/class-dataset.php';
require_once ABB_WCFE_BD_DIR . 'includes/class-admin.php';
require_once ABB_WCFE_BD_DIR . 'includes/class-frontend.php';

register_activation_hook( __FILE__, function() {
	\ABB\WCFE_BD\Admin::install_defaults();
});

add_action( 'plugins_loaded', function() {
	// Since WP 4.6, wp.org auto-loads translations for the plugin slug.
	// No need to call load_plugin_textdomain() (Plugin Check discourages it).
	\ABB\WCFE_BD\Admin::init();
	\ABB\WCFE_BD\Frontend::init();
});
