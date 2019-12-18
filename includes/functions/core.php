<?php
/**
 * Core plugin functionality.
 *
 * @package WpCleanMedia
 */

namespace WpCleanMedia\Core;

use \WP_Error as WP_Error;

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	// enqueue admin scripts.
	add_action( 'admin_enqueue_scripts', $n( 'admin_scripts' ) );

	// add link to admin menu.
	add_action( 'admin_menu', $n( 'admin_menu' ) );

	// pre filter posts to show only unused files.
	add_action( 'pre_get_posts', $n( 'filter_posts' ) );
}

/**
 * Filter posts
 *
 * @param object $query Current query.
 *
 * @return object
 */
function filter_posts( $query ) {
	if ( is_admin() && $query->is_main_query() ) {
		$attachment_filter = filter_input( INPUT_GET, 'attachment-filter', FILTER_SANITIZE_STRING );
		if ( 'unused' === $attachment_filter ) {
			$meta_query         = array();
			$meta_query[]       = array(
				'key'     => 'wpcm_used',
				'value'   => 'no',
				'compare' => '=',
			);
			$current_meta_query = $query->meta_query;
			if ( $current_meta_query ) {
				$meta_query = array_merge( $current_meta_query, $meta_query );
			}
			$query->set( 'meta_query', $meta_query );
		}
	}
	return $query;
}

/**
 * Add Link to Admin Menu
 *
 * @return void
 */
function admin_menu() {
	add_media_page(
		__( 'WP Clean Media', 'wpcm' ),
		__( 'Clean Files', 'wpcm' ),
		'manage_options',
		'wp-clean-media',
		__NAMESPACE__ . '\clean_media_screen'
	);
};

/**
 * Clean Media Screen
 */
function clean_media_screen() {
	$stats = \WpCleanMedia\Scan\get_stats();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'WP Clean Media', 'wpcm' ); ?></h1>
		<p><?php echo esc_html__( 'In this section you will be able to scan the entire site for files that are not being used. Please use this plugin with caution.', 'wpcm' ); ?></p>

		<h2><?php echo esc_html__( 'Quick stats:', 'wpcm' ); ?></h2>
		<div id="dashboard-widgets">
			<div class="postbox-container">
				<div class="postbox">
					<div class="inside">
						<div class="main">
							<ul>
								<li>Total attachments: <?php echo esc_html( $stats['total_posts'] ); ?></li>
								<li>Scanned: <?php echo esc_html( $stats['total_scanned'] ); ?> </li>
							</ul>
							<div id="wpcm-scanning">
								<span class="spinner is-active"></span> Scan in progress
							</div>
							<?php if ( ! \WpCleanMedia\Scan\is_active() ) : ?>
								<script>var wpcm_is_running = false;</script>
								<button type="button" class="button button-primary" id="wpcm-scan-now">Scan Now</button>
							<?php else : ?>
								<script>var wpcm_is_running = true;</script>
								<button type="button" class="button button-primary" id="wpcm-stop-now">Stop Scan</button>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Activate the plugin
 *
 * @return void
 */
function activate() {
	// First load the init scripts in case any rewrite functionality is being loaded.
	init();
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @return void
 */
function deactivate() {

}

/**
 * Enqueue scripts for admin.
 *
 * @return void
 */
function admin_scripts() {

	wp_enqueue_script(
		'wp_clean_media_admin',
		WP_CLEAN_MEDIA_URL . 'assets/js/admin.js',
		array(),
		WP_CLEAN_MEDIA_VERSION,
		true
	);

	wp_enqueue_style(
		'wp_clean_media_admin',
		WP_CLEAN_MEDIA_URL . 'assets/css/admin-style.css',
		array(),
		WP_CLEAN_MEDIA_VERSION
	);

}
