<?php
use PHPUnit\Framework\TestCase;

class RestApiTest extends TestCase {
    protected function setUp(): void {
        global $sys_wp_insert_post_return, $sys_post_meta, $sys_wp_posts;
        $sys_wp_insert_post_return = 1;
        $sys_post_meta = [];
        $sys_wp_posts = [];
    }

    public function test_sys_save_route_success() {
        global $sys_wp_insert_post_return, $sys_post_meta;
        $sys_wp_insert_post_return = 123;

        $request  = new WP_REST_Request( [ 'route' => json_encode( [ 'coords' => [ 1, 2 ] ] ) ] );
        $response = sys_save_route( $request );

        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $this->assertSame( [ 'id' => 123, 'route' => [ 'coords' => [ 1, 2 ] ] ], $response->get_data() );
        $this->assertSame( json_encode( [ 'coords' => [ 1, 2 ] ] ), $sys_post_meta[123]['_sys_route_data'] );
    }

    public function test_sys_save_route_missing_route() {
        $request = new WP_REST_Request();
        $result  = sys_save_route( $request );
        $this->assertTrue( is_wp_error( $result ) );
        $this->assertSame( 'sys_invalid_route', $result->get_error_code() );
    }

    public function test_sys_save_route_invalid_json() {
        $request = new WP_REST_Request( [ 'route' => '{invalid' ] );
        $result  = sys_save_route( $request );
        $this->assertTrue( is_wp_error( $result ) );
        $this->assertSame( 'sys_invalid_route', $result->get_error_code() );
    }

    public function test_sys_save_route_insert_error() {
        global $sys_wp_insert_post_return;
        $sys_wp_insert_post_return = new WP_Error( 'insert_failed', 'error' );
        $request = new WP_REST_Request( [ 'route' => json_encode( [] ) ] );
        $result  = sys_save_route( $request );
        $this->assertTrue( is_wp_error( $result ) );
        $this->assertSame( 'insert_failed', $result->get_error_code() );
    }

    public function test_sys_get_routes_returns_valid_routes_only() {
        global $sys_wp_posts, $sys_post_meta;
        $sys_wp_posts = [
            1 => 'sys_route',
            2 => 'sys_route',
            3 => 'sys_route',
        ];
        $sys_post_meta[1]['_sys_route_data'] = json_encode( [ 'a' => 1 ] );
        $sys_post_meta[2]['_sys_route_data'] = 'invalid';
        $sys_post_meta[3]['_sys_route_data'] = json_encode( [ 'b' => 2 ] );

        $response = sys_get_routes( new WP_REST_Request() );
        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $data = $response->get_data();
        $this->assertCount( 2, $data['routes'] );
        $this->assertSame( 1, $data['routes'][0]['id'] );
        $this->assertSame( [ 'a' => 1 ], $data['routes'][0]['route'] );
        $this->assertSame( 3, $data['routes'][1]['id'] );
    }

    public function test_sys_get_chat_success() {
        $request  = new WP_REST_Request( [ 'message' => 'Hello' ] );
        $response = sys_get_chat( $request );
        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $this->assertSame( [ 'message' => 'Hello' ], $response->get_data() );
    }

    public function test_sys_get_chat_message_too_long() {
        $request = new WP_REST_Request( [ 'message' => str_repeat( 'a', 201 ) ] );
        $result  = sys_get_chat( $request );
        $this->assertTrue( is_wp_error( $result ) );
        $this->assertSame( 'sys_message_too_long', $result->get_error_code() );
    }

    public function test_sys_get_chat_disallowed_content() {
        $request = new WP_REST_Request( [ 'message' => 'this has spam' ] );
        $result  = sys_get_chat( $request );
        $this->assertTrue( is_wp_error( $result ) );
        $this->assertSame( 'sys_message_disallowed', $result->get_error_code() );
    }

    public function test_rest_route_permissions() {
        global $sys_registered_routes, $sys_current_user_cap;

        $expected = [
            'share-your-steps/v1/save-route'    => 'edit_posts',
            'share-your-steps/v1/live-route'    => 'edit_posts',
            'share-your-steps/v1/finalize-route'=> 'edit_posts',
            'share-your-steps/v1/routes'        => 'read',
            'share-your-steps/v1/chat'          => 'read',
        ];

        foreach ( $expected as $route => $cap ) {
            $sys_current_user_cap = '';
            $callback             = $sys_registered_routes[ $route ]['permission_callback'];
            $this->assertTrue( $callback() );
            $this->assertSame( $cap, $sys_current_user_cap );
        }
    }

    public function test_sys_cleanup_old_live_routes_only_cleans_sys_route_posts() {
        global $sys_wp_posts, $sys_post_meta;

        $now = time();

        $sys_wp_posts = [
            1 => 'sys_route',
            2 => 'post',
            3 => 'sys_route',
        ];

        $sys_post_meta[1]['_sys_live_coords']          = 'data1';
        $sys_post_meta[1]['_sys_live_coords_updated']  = $now - WEEK_IN_SECONDS - 10;
        $sys_post_meta[2]['_sys_live_coords']          = 'data2';
        $sys_post_meta[2]['_sys_live_coords_updated']  = $now - WEEK_IN_SECONDS - 10;
        $sys_post_meta[3]['_sys_live_coords']          = 'data3';
        $sys_post_meta[3]['_sys_live_coords_updated']  = $now - WEEK_IN_SECONDS + 10;

        sys_cleanup_old_live_routes();

        $this->assertArrayNotHasKey( '_sys_live_coords', $sys_post_meta[1] );
        $this->assertArrayNotHasKey( '_sys_live_coords_updated', $sys_post_meta[1] );

        $this->assertArrayHasKey( '_sys_live_coords', $sys_post_meta[2] );
        $this->assertArrayHasKey( '_sys_live_coords_updated', $sys_post_meta[2] );

        $this->assertArrayHasKey( '_sys_live_coords', $sys_post_meta[3] );
        $this->assertArrayHasKey( '_sys_live_coords_updated', $sys_post_meta[3] );
    }
}
