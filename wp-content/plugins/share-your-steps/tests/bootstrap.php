<?php
// Define ABSPATH to bypass direct access restrictions.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

// -----------------------------------------------------------------------------
// Basic WordPress stubs required for loading the plugin.
// -----------------------------------------------------------------------------
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_shortcode( $tag, $callback ) {}
function wp_enqueue_style( ...$args ) {}
function wp_enqueue_script( ...$args ) {}
function wp_script_add_data( ...$args ) {}
function wp_localize_script( ...$args ) {}
function wp_die( $message ) { throw new Exception( $message ); }
function load_plugin_textdomain( ...$args ) {}
function is_admin() { return false; }
function wp_is_https_supported() { return true; }
function apply_filters( $tag, $value ) { return $value; }
function is_ssl() { return true; }
function wp_safe_redirect( $location, $status ) {}
function register_post_type( $post_type, $args = array() ) {}
function plugins_url( $path = '', $plugin = '' ) { return $path; }
function plugin_basename( $file ) { return basename( $file ); }
function esc_url_raw( $url ) { return $url; }
function esc_attr( $text ) { return $text; }
function esc_html( $text ) { return $text; }
function __( $text, $domain = null ) { return $text; }
function wp_rand() { return 1; }

// -----------------------------------------------------------------------------
// Transient API stubs.
// -----------------------------------------------------------------------------
global $sys_test_transients;
$sys_test_transients = [];

function set_transient( $key, $value, $expiration ) {
    global $sys_test_transients;
    $sys_test_transients[ $key ] = [
        'value'      => $value,
        'expiration' => time() + (int) $expiration,
    ];
    return true;
}

function get_transient( $key ) {
    global $sys_test_transients;
    if ( ! isset( $sys_test_transients[ $key ] ) ) {
        return false;
    }
    if ( $sys_test_transients[ $key ]['expiration'] < time() ) {
        unset( $sys_test_transients[ $key ] );
        return false;
    }
    return $sys_test_transients[ $key ]['value'];
}

function delete_transient( $key ) {
    global $sys_test_transients;
    unset( $sys_test_transients[ $key ] );
    return true;
}

// -----------------------------------------------------------------------------
// Additional stubs for REST API and database interactions.
// -----------------------------------------------------------------------------
class WP_Error {
    private $code;
    private $message;
    private $data;
    public function __construct( $code = '', $message = '', $data = [] ) {
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}

function is_wp_error( $thing ) { return $thing instanceof WP_Error; }

class WP_REST_Request {
    private $params = [];
    public function __construct( $params = [] ) { $this->params = $params; }
    public function get_param( $key ) { return $this->params[ $key ] ?? null; }
    public function set_param( $key, $value ) { $this->params[ $key ] = $value; }
}

class WP_REST_Response {
    private $data;
    public function __construct( $data ) { $this->data = $data; }
    public function get_data() { return $this->data; }
}

function rest_ensure_response( $data ) { return new WP_REST_Response( $data ); }

class WP_REST_Server {
    const READABLE = 'GET';
    const CREATABLE = 'POST';
}

global $sys_registered_routes;
$sys_registered_routes = [];
function register_rest_route( $namespace, $route, $args ) {
    global $sys_registered_routes;
    $sys_registered_routes[ $namespace . $route ] = $args;
}

global $sys_current_user_cap;
$sys_current_user_cap = '';
function current_user_can( $cap ) {
    global $sys_current_user_cap;
    $sys_current_user_cap = $cap;
    return true;
}
function sanitize_text_field( $str ) { return trim( $str ); }
function wp_unslash( $value ) { return $value; }
function wp_json_encode( $data ) { return json_encode( $data ); }
function current_time( $type ) { return '2020-01-01 00:00:00'; }

// Storage for mock posts and metadata.
global $sys_wp_insert_post_return, $sys_post_meta, $sys_wp_query_posts;
$sys_wp_insert_post_return = 1;
$sys_post_meta            = [];
$sys_wp_query_posts       = [];

function wp_insert_post( $data, $wp_error = false ) {
    global $sys_wp_insert_post_return;
    return $sys_wp_insert_post_return;
}

function update_post_meta( $post_id, $key, $value ) {
    global $sys_post_meta;
    $sys_post_meta[ $post_id ][ $key ] = $value;
    return true;
}

function get_post_meta( $post_id, $key, $single = false ) {
    global $sys_post_meta;
    return $sys_post_meta[ $post_id ][ $key ] ?? '';
}

class WP_Query {
    public $posts;
    public function __construct( $args ) {
        global $sys_wp_query_posts;
        $this->posts = $sys_wp_query_posts;
    }
}

// Provide stub for current user ID.
function get_current_user_id() {
    return 0;
}

// -----------------------------------------------------------------------------
// Load plugin code and register REST routes.
// -----------------------------------------------------------------------------
require dirname( __DIR__ ) . '/includes/utils.php';
require dirname( __DIR__ ) . '/share-your-steps.php';

sys_register_rest_routes();
