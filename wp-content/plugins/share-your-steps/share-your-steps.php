<?php
/**
 * Plugin Name: Share Your Steps
 * Description: Display a Leaflet map to explore shared steps.
 * Version: 1.0.0
 * Author: Share Your Steps Team
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: share-your-steps
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    wp_die( esc_html( __( 'No direct script access allowed.', 'share-your-steps' ) ) );
}

// Load plugin text domain.
function sys_load_textdomain() {
    load_plugin_textdomain( 'share-your-steps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'sys_load_textdomain' );

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
    wp_localize_script(
        'share-your-steps',
        'shareYourSteps',
        array(
            'api_url' => esc_url_raw( rest_url( 'share-your-steps/v1/' ) ),
        )
    );
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

    return '<div id="' . esc_attr( $map_id ) . '" class="sys-map" data-lat="' . esc_attr( $atts['lat'] ) . '" data-lng="' . esc_attr( $atts['lng'] ) . '" data-zoom="' . esc_attr( $atts['zoom'] ) . '">' . esc_html( __( 'Loading map...', 'share-your-steps' ) ) . '</div>';
}
add_shortcode( 'share_your_steps', 'sys_share_your_steps_shortcode' );

// Force HTTPS for all front-end requests.
function sys_force_https() {
    if ( ! is_ssl() ) {
        $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        wp_safe_redirect( $location, 301 );
        exit;
    }
}
add_action( 'template_redirect', 'sys_force_https', 1 );

// Register REST API routes.
function sys_register_rest_routes() {
    register_rest_route(
        'share-your-steps/v1',
        '/save-route',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'sys_save_route',
            'permission_callback' => function () {
                return current_user_can( 'read' );
            },
        )
    );

    register_rest_route(
        'share-your-steps/v1',
        '/routes',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'sys_get_routes',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'share-your-steps/v1',
        '/chat',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'sys_get_chat',
            'permission_callback' => function () {
                return current_user_can( 'read' );
            },
        )
    );
}
add_action( 'rest_api_init', 'sys_register_rest_routes' );

// Save a user route.
function sys_save_route( WP_REST_Request $request ) {
    $route = sanitize_text_field( $request->get_param( 'route' ) );

    return rest_ensure_response( array( 'route' => $route ) );
}

// Retrieve stored routes.
function sys_get_routes( WP_REST_Request $request ) {
    return rest_ensure_response( array( 'routes' => array() ) );
}

// Handle chat messages with basic anti-spam checks.
function sys_get_chat( WP_REST_Request $request ) {
    $message = sanitize_text_field( $request->get_param( 'message' ) );

    $max_length = 200;
    $blacklist  = array( 'spam', 'viagra' );

    if ( strlen( $message ) > $max_length ) {
        return new WP_Error( 'sys_message_too_long', __( 'Message too long.', 'share-your-steps' ), array( 'status' => 400 ) );
    }

    foreach ( $blacklist as $word ) {
        if ( false !== stripos( $message, $word ) ) {
            return new WP_Error( 'sys_message_disallowed', __( 'Message contains disallowed content.', 'share-your-steps' ), array( 'status' => 400 ) );
        }
    }

    return rest_ensure_response( array( 'message' => $message ) );
}

// AJAX callback for backward compatibility.
function sys_handle_message_ajax() {
    if ( ! current_user_can( 'read' ) ) {
        wp_send_json_error( __( 'Unauthorized', 'share-your-steps' ), 403 );
    }

    $request = new WP_REST_Request( 'POST', '/chat' );
    $request->set_param( 'message', isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '' );
    $response = sys_get_chat( $request );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message(), $response->get_error_data()['status'] ?? 400 );
    }

    wp_send_json_success( $response->get_data() );
}
add_action( 'wp_ajax_sys_handle_message', 'sys_handle_message_ajax' );
add_action( 'wp_ajax_nopriv_sys_handle_message', 'sys_handle_message_ajax' );
