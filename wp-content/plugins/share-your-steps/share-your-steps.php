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
add_action( 'wp_enqueue_scripts', 'sys_enqueue_leaflet_assets' );

// Shortcode to render map
function sys_share_your_steps_shortcode( $atts = array() ) {
    $atts = shortcode_atts( array(
        'lat' => '0',
        'lng' => '0',
        'zoom' => '13',
    ), $atts, 'share_your_steps' );

    $map_id = 'sys-map-' . wp_rand();

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $map_id ); ?>" style="width:100%;height:400px;"></div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('<?php echo esc_js( $map_id ); ?>').setView([<?php echo esc_js( $atts['lat'] ); ?>, <?php echo esc_js( $atts['lng'] ); ?>], <?php echo esc_js( $atts['zoom'] ); ?>);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'share_your_steps', 'sys_share_your_steps_shortcode' );
