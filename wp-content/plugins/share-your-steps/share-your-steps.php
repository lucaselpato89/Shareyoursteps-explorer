<?php
/**
 * Plugin Name: Share Your Steps
 * Description: Display a Leaflet map to explore shared steps.
 * Version: 1.0.0
 * Author: Share Your Steps Team
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enqueue Leaflet assets from CDN.
function sys_enqueue_leaflet_assets() {
    wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
    wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
}

// Enqueue plugin assets.
function sys_enqueue_assets() {
    wp_enqueue_style( 'share-your-steps', plugins_url( 'assets/css/share-your-steps.min.css', __FILE__ ), array(), '1.0.0' );
    wp_enqueue_script( 'share-your-steps', plugins_url( 'assets/js/map.min.js', __FILE__ ), array( 'leaflet' ), '1.0.0', true );
    wp_script_add_data( 'share-your-steps', 'type', 'module' );
}

add_action( 'wp_enqueue_scripts', 'sys_enqueue_leaflet_assets' );
add_action( 'wp_enqueue_scripts', 'sys_enqueue_assets' );

// Shortcode to render map
function sys_share_your_steps_shortcode( $atts = array() ) {
    $atts = shortcode_atts( array(
        'lat' => '0',
        'lng' => '0',
        'zoom' => '13',
    ), $atts, 'share_your_steps' );

    $map_id = 'sys-map-' . wp_rand();

    return '<div id="' . esc_attr( $map_id ) . '" class="sys-map" data-lat="' . esc_attr( $atts['lat'] ) . '" data-lng="' . esc_attr( $atts['lng'] ) . '" data-zoom="' . esc_attr( $atts['zoom'] ) . '"></div>';
}
add_shortcode( 'share_your_steps', 'sys_share_your_steps_shortcode' );
