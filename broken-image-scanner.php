<?php

/**
  * The plugin bootstrap file
  *
  * @link              https://robertdevore.com
  * @since             1.0.0
  * @package           Broken_Image_Scanner
  *
  * @wordpress-plugin
  *
  * Plugin Name: Broken Image Scanner
  * Description: Scans your site for broken image URLs, displays results in a modern UI, and includes a progress bar.
  * Plugin URI:  https://github.com/robertdevore/broken-image-scanner/
  * Version:     1.0.0
  * Author:      Robert DeVore
  * Author URI:  https://robertdevore.com/
  * License:     GPL-2.0+
  * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain: broken-image-scanner
  * Domain Path: /languages
  * Update URI:  https://github.com/robertdevore/broken-image-scanner/
  */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Add the Plugin Update Checker.
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/broken-image-scanner/',
    __FILE__,
    'broken-image-scanner'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Define constants.
define('BROKEN_IMAGE_SCANNER_VERSION', '1.0.0' );

/**
 * Add a settings page under the Media menu.
 * 
 * @since  1.0.0
 * @return void
 */
function ebis_add_settings_page() {
    add_submenu_page(
        'upload.php',
        __( 'Broken Images', 'enhanced-broken-image-scanner' ),
        __( 'Broken Images', 'enhanced-broken-image-scanner' ),
        'manage_options',
        'enhanced-broken-image-scanner',
        'ebis_settings_page_content'
    );
}
add_action( 'admin_menu', 'ebis_add_settings_page' );

/**
 * Enqueue custom styles and scripts.
 * 
 * @since  1.0.0
 * @return void
 */
function ebis_enqueue_admin_assets( $hook ) {
    if ( $hook !== 'media_page_enhanced-broken-image-scanner' ) {
        return;
    }

    wp_enqueue_style( 'ebis-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css', [], BROKEN_IMAGE_SCANNER_VERSION );
    wp_enqueue_script( 'ebis-admin-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin-script.js', [ 'jquery' ], BROKEN_IMAGE_SCANNER_VERSION, true );
    wp_localize_script( 'ebis-admin-script', 'ebisAjax', [
        'ajaxurl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ebis_scan_nonce' ),
        'siteurl'  => get_site_url(),
        'sitename' => sanitize_title( get_bloginfo( 'name' ) ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'ebis_enqueue_admin_assets' );

/**
 * Render the settings page content.
 * 
 * @since  1.0.0
 * @return void
 */
function ebis_settings_page_content() {
    ?>
    <div class="ebis-wrapper">
        <h1>
            <?php esc_html_e( 'Broken Image Scanner', 'enhanced-broken-image-scanner' ); ?>
            <button id="start-scan" class="button button-primary">
                <?php esc_html_e( 'Start Scan', 'enhanced-broken-image-scanner' ); ?>
            </button>
        </h1>
        <div class="ebis-container">
            <div id="progress-bar">
                <div id="progress"></div>
            </div>
            <p id="scan-status"></p>
            <div id="scan-results"></div>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler for scanning images.
 * 
 * @since  1.0.0
 * @return void
 */
function ebis_scan_images() {
    check_ajax_referer( 'ebis_scan_nonce', 'nonce' );

    global $wpdb;

    // Get request parameters.
    $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
    $limit  = 10; // Number of posts to process per batch

    // Get all public CPTs.
    $post_types = get_post_types( [ 'public' => true ], 'names' );

    // Fetch posts in batches.
    $posts = $wpdb->get_results( $wpdb->prepare(
        "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "','", $post_types ) . "') AND post_status = 'publish' LIMIT %d OFFSET %d",
        $limit,
        $offset
    ) );

    $total_posts = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "','", $post_types ) . "') AND post_status = 'publish'" );

    $broken_images = [];
    foreach ( $posts as $post ) {
        if ( preg_match_all( '/<img[^>]+src="([^"]+)"/', $post->post_content, $matches ) ) {
            foreach ( $matches[1] as $image_url ) {
                $response = wp_remote_head( $image_url );
                if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                    $broken_images[] = [
                        'post_id'    => $post->ID,
                        'post_title' => get_the_title( $post->ID ),
                        'image_url'  => esc_url( $image_url ),
                    ];
                }
            }
        }
    }

    // Calculate progress.
    $new_offset = $offset + $limit;
    $progress   = min( round( ( $new_offset / $total_posts ) * 100 ), 100 );

    // Return response for this batch.
    wp_send_json( [
        'progress'      => $progress,
        'broken_images' => $broken_images,
        'offset'        => $new_offset,
        'completed'     => $new_offset >= $total_posts,
    ] );
}
add_action( 'wp_ajax_ebis_scan_images', 'ebis_scan_images' );
