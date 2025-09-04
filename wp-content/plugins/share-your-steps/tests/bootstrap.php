<?php
// Minimal stubs to simulate WordPress transient API for tests.

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

// Provide stub for current user ID.
function get_current_user_id() {
    return 0;
}

require dirname( __DIR__ ) . '/includes/utils.php';

