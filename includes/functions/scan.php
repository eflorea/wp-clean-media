<?php
/**
 * Scan plugin functionality.
 *
 * @package WpCleanMedia
 */

namespace WpCleanMedia\Scan;

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

	// init action.
	add_action( 'init', $n( 'init' ) );

	// scan attachments.
	add_action( 'wp_ajax_wpcm_scan', $n( 'scan_attachments' ) );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {
}

/**
 * Check if an active scan exists
 *
 * @return bool
 */
function is_active() {
	if ( 'yes' === get_option( 'wpcm_scanning', 'no' ) ) {
		return true;
	}

	return false;
}

/**
 * Start scanning
 */
function start_scan() {
	update_option( 'wpcm_scanning', 'yes' );
}

/**
 * End/Pause scan
 */
function stop_scan() {
	update_option( 'wpcm_scanning', 'no' );
}

/**
 * Get quick stats
 *
 * @return array
 */
function get_stats() {
	// get total attachments.
	$args        = array(
		'post_type'      => 'attachment',
		'post_status'    => array( 'inherit', 'private' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	);
	$query       = new \WP_Query( $args );
	$total_posts = intval( $query->found_posts );

	// get total parsed.
	$args          = array(
		'post_type'      => 'attachment',
		'post_status'    => array( 'inherit', 'private' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore
			array(
				'key'     => 'wpcm_used',
				'compare' => 'EXISTS',
			),
		),
	);
	$query         = new \WP_Query( $args );
	$total_scanned = intval( $query->found_posts );

	return array(
		'total_posts'   => $total_posts,
		'total_scanned' => $total_scanned,
	);
}

/**
 * Scan attachments
 */
function scan_attachments() {
	if ( ! isset( $_POST['action'] ) ) {
		return;
	}
	// check if this is a first request to scan.
	$start_scan = intval( filter_input( INPUT_POST, 'wpcm_initiate_scan', FILTER_SANITIZE_NUMBER_INT ) );
	if ( 1 === $start_scan ) {
		start_scan();
	}

	// check if scanning is active.
	if ( ! is_active() ) {
		return;
	}

	global $wpdb;

	$how_many_to_scan = 50;
	$args             = array(
		'post_type'      => 'attachment',
		'post_status'    => array( 'inherit', 'private' ),
		'posts_per_page' => ( $how_many_to_scan + 1 ),
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore
			array(
				'key'     => 'wpcm_used',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	$query            = new \WP_Query( $args );
	$posts            = $query->posts;
	if ( $posts ) {
		global $wpdb;
		foreach ( $posts as $post_id ) {
			$full_path = get_attached_file( $post_id );
			$main_file = clean_file_name( $full_path );
			// check if it's beeing used.
			$post = get_post( $post_id );
			if ( $post->post_parent ) {
				$parent = get_post( $post->post_parent );
				if ( $parent ) {
					// has parent.
					update_post_meta( $post_id, 'wpcm_used', 'yes' );
					continue;
				}
			}
			$results   = $wpdb->get_results( $wpdb->prepare( 'select ID, post_title from ' . $wpdb->posts . ' where post_type in ( %s, %s, %s ) and post_content like %s limit %d', 'post', 'page', 'reusable-content', '%' . $main_file . '%', 10 ) ); // phpcs:ignore
			if ( $results ) {
				update_post_meta( $post_id, 'wpcm_used', 'yes' );
			} else {
				update_post_meta( $post_id, 'wpcm_used', 'no' );
			}
		}
	}

	$next_page = false;
	if ( count( $posts ) > $how_many_to_scan ) {
		$next_page = true;
	}

	// get stats.
	$stats              = get_stats();
	$stats['next_page'] = $next_page;

	wp_send_json( $stats );
	die;
}

/**
 * From a fullpath to the shortened and cleaned path (for example '2013/02/file.png').
 *
 * @param string $path File path.
 *
 * @return string
 *
 */
function clean_file_name( $path ) {
	$content_dir = substr( wp_upload_dir()['baseurl'], 1 + strlen( get_site_url() ) );
	$dir_index   = strpos( $path, $content_dir );
	if ( falce === $dir_index ) {
		$file = $path;
	} else {
		// Remove first part of the path leaving yyyy/mm/filename.ext.
		$file = substr( $path, 1 + strlen( $content_dir ) + $dir_index );
	}
	if ( './' === substr( $file, 0, 2 ) ) {
		$file = substr( $file, 2 );
	}
	if ( '/' === substr( $file, 0, 1 ) ) {
		$file = substr( $file, 1 );
	}
	return $file;
}
