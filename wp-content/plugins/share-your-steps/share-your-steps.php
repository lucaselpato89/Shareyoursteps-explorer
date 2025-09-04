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
            // Configure the WebSocket endpoint (e.g. Pusher, Socket.io, Ratchet).
            'websocket_url' => esc_url_raw( 'ws://localhost:8080' ),
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

// Register custom post type for routes.
function sys_register_route_post_type() {
    register_post_type(
        'sys_route',
        array(
            'labels' => array(
                'name'          => __( 'Routes', 'share-your-steps' ),
                'singular_name' => __( 'Route', 'share-your-steps' ),
            ),
            'public'       => false,
            'show_ui'      => false,
            'supports'     => array( 'title' ),
            'capability_type' => 'sys_route',
            'map_meta_cap'    => true,
        )
    );
}
add_action( 'init', 'sys_register_route_post_type' );

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
            'permission_callback' => function () {
                return current_user_can( 'read' );
            },
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

    register_rest_route(
        'share-your-steps/v1',
        '/live-route',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'sys_save_live_route',
            'permission_callback' => function () {
                return current_user_can( 'read' );
            },
        )
    );

    register_rest_route(
        'share-your-steps/v1',
        '/finalize-route',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'sys_finalize_route',
            'permission_callback' => function () {
                return current_user_can( 'read' );
            },
        )
    );
}
add_action( 'rest_api_init', 'sys_register_rest_routes' );

// Save a user route.
function sys_save_route( WP_REST_Request $request ) {
    $route_raw = $request->get_param( 'route' );

    if ( empty( $route_raw ) ) {
        return new WP_Error( 'sys_invalid_route', __( 'No route data provided.', 'share-your-steps' ), array( 'status' => 400 ) );
    }

    $route_data = json_decode( wp_unslash( $route_raw ), true );
    if ( null === $route_data || JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error( 'sys_invalid_route', __( 'Invalid route JSON.', 'share-your-steps' ), array( 'status' => 400 ) );
    }

    $post_id = wp_insert_post(
        array(
            'post_type'   => 'sys_route',
            'post_status' => 'publish',
            'post_title'  => 'Route ' . current_time( 'mysql' ),
            'post_author' => get_current_user_id(),
        ),
        true
    );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    update_post_meta( $post_id, '_sys_route_data', wp_json_encode( $route_data ) );

    return rest_ensure_response( array( 'id' => $post_id, 'route' => $route_data ) );
}

// Retrieve stored routes.
function sys_get_routes( WP_REST_Request $request ) {
    $args = array(
        'post_type'      => 'sys_route',
        'post_status'    => 'publish',
        'author'         => get_current_user_id(),
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    $query  = new WP_Query( $args );
    $routes = array();

    foreach ( $query->posts as $post_id ) {
        $route = get_post_meta( $post_id, '_sys_route_data', true );
        if ( empty( $route ) ) {
            continue;
        }

        $decoded = json_decode( $route, true );
        if ( JSON_ERROR_NONE === json_last_error() ) {
            $routes[] = array(
                'id'    => $post_id,
                'route' => $decoded,
            );
        }
    }

    return rest_ensure_response( array( 'routes' => $routes ) );
}

// Schedule event to store live coordinates.
function sys_save_live_route( WP_REST_Request $request ) {
    $post_id = absint( $request->get_param( 'post_id' ) );
    $coords  = $request->get_param( 'coords' );

    if ( ! $post_id || empty( $coords ) || ! is_array( $coords ) ) {
        return new WP_Error( 'sys_invalid_data', __( 'Invalid data.', 'share-your-steps' ), array( 'status' => 400 ) );
    }

    wp_schedule_single_event( time(), 'sys_store_live_coordinates', array( $post_id, $coords ) );

    return rest_ensure_response( array( 'status' => 'scheduled' ) );
}
add_action( 'sys_store_live_coordinates', 'sys_store_live_coordinates', 10, 2 );

/**
 * Store live coordinates in post meta.
 *
 * @param int   $post_id Post ID.
 * @param array $coords  Coordinates array.
 */
function sys_store_live_coordinates( $post_id, $coords ) {
    if ( ! $post_id || ! is_array( $coords ) ) {
        return;
    }

    $stored = get_post_meta( $post_id, '_sys_live_coords', true );
    if ( ! is_array( $stored ) ) {
        $stored = array();
    }

    $stored[] = array(
        'lat'  => isset( $coords['lat'] ) ? (float) $coords['lat'] : 0,
        'lng'  => isset( $coords['lng'] ) ? (float) $coords['lng'] : 0,
        'time' => time(),
    );

    update_post_meta( $post_id, '_sys_live_coords', $stored );
    update_post_meta( $post_id, '_sys_live_coords_updated', time() );
}

// Convert live route to final route.
function sys_finalize_route( WP_REST_Request $request ) {
    $post_id = absint( $request->get_param( 'post_id' ) );

    if ( ! $post_id ) {
        return new WP_Error( 'sys_invalid_post', __( 'Invalid post ID.', 'share-your-steps' ), array( 'status' => 400 ) );
    }

    $coords = get_post_meta( $post_id, '_sys_live_coords', true );

    if ( empty( $coords ) ) {
        return new WP_Error( 'sys_no_route', __( 'No live route found.', 'share-your-steps' ), array( 'status' => 404 ) );
    }

    update_post_meta( $post_id, '_sys_final_route', $coords );
    delete_post_meta( $post_id, '_sys_live_coords' );
    delete_post_meta( $post_id, '_sys_live_coords_updated' );

    return rest_ensure_response( array( 'status' => 'finalized', 'route' => $coords ) );
}

// Schedule daily cleanup of stale live routes.
function sys_schedule_live_route_cleanup() {
    if ( ! wp_next_scheduled( 'sys_cleanup_live_routes' ) ) {
        wp_schedule_event( time(), 'daily', 'sys_cleanup_live_routes' );
    }
}
add_action( 'init', 'sys_schedule_live_route_cleanup' );
add_action( 'sys_cleanup_live_routes', 'sys_cleanup_old_live_routes' );

// Remove live routes older than a week.
function sys_cleanup_old_live_routes() {
    $args  = array(
        'post_type'      => 'any',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => '_sys_live_coords_updated',
                'value'   => time() - WEEK_IN_SECONDS,
                'compare' => '<',
                'type'    => 'NUMERIC',
            ),
        ),
    );

    $query = new WP_Query( $args );

    foreach ( $query->posts as $post_id ) {
        delete_post_meta( $post_id, '_sys_live_coords' );
        delete_post_meta( $post_id, '_sys_live_coords_updated' );
    }
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
