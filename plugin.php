<?php
/**
 * Plugin Name: WpCleanMedia
 * Plugin URI:
 * Description:
 * Version:     0.1.0
 * Author:      10up
 * Author URI:  https://10up.com
 * Text Domain: wp-clean-media
 * Domain Path: /languages
 *
 * @package WpCleanMedia
 */

// Useful global constants.
define( 'WP_CLEAN_MEDIA_VERSION', '0.1.0' );
define( 'WP_CLEAN_MEDIA_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_CLEAN_MEDIA_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_CLEAN_MEDIA_INC', WP_CLEAN_MEDIA_PATH . 'includes/' );

// Include files.
require_once WP_CLEAN_MEDIA_INC . 'functions/core.php';

// Activation/Deactivation.
register_activation_hook( __FILE__, '\WpCleanMedia\Core\activate' );
register_deactivation_hook( __FILE__, '\WpCleanMedia\Core\deactivate' );

// Bootstrap.
WpCleanMedia\Core\setup();

// Require Composer autoloader if it exists.
if ( file_exists( WP_CLEAN_MEDIA_PATH . '/vendor/autoload.php' ) ) {
	require_once WP_CLEAN_MEDIA_PATH . 'vendor/autoload.php';
}
