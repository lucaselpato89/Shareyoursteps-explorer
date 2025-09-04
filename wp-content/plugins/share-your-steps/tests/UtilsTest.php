<?php
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {
    public function test_haversine() {
        $distance = sys_haversine_km(0, 0, 0, 1); // ~111.32 km along equator
        $this->assertEqualsWithDelta(111.32, $distance, 0.5);
    }

    public function test_rate_limiter() {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        unset( $_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
        $key       = 'test';
        $limit     = 2;
        $interval  = 1; // Use short interval for tests.
        $transient = 'sys_rate_' . $key . '_ip_' . $_SERVER['REMOTE_ADDR'];

        $this->assertTrue( sys_rate_limiter( $key, $limit, $interval ) );
        $this->assertTrue( sys_rate_limiter( $key, $limit, $interval ) );
        $this->assertFalse( sys_rate_limiter( $key, $limit, $interval ) );

        // Transient should exist after hitting the limit.
        $this->assertIsArray( get_transient( $transient ) );

        // Wait for interval to expire and ensure transient is cleared.
        sleep( $interval + 1 );
        $this->assertFalse( get_transient( $transient ) );

        // New window should allow requests again.
        $this->assertTrue( sys_rate_limiter( $key, $limit, $interval ) );
    }

    public function test_rate_limiter_forwarded_ip() {
        $_SERVER['REMOTE_ADDR']       = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8, 1.1.1.1';
        unset( $_SERVER['HTTP_CLIENT_IP'] );

        $key       = 'fwd';
        $limit     = 1;
        $interval  = 1;
        $transient = 'sys_rate_' . $key . '_ip_8.8.8.8';

        $this->assertTrue( sys_rate_limiter( $key, $limit, $interval ) );
        $this->assertIsArray( get_transient( $transient ) );

        delete_transient( $transient );
        unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
    }

    public function test_rate_limiter_client_ip() {
        $_SERVER['REMOTE_ADDR']     = '127.0.0.1';
        $_SERVER['HTTP_CLIENT_IP']  = '9.9.9.9';
        unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

        $key       = 'client';
        $limit     = 1;
        $interval  = 1;
        $transient = 'sys_rate_' . $key . '_ip_9.9.9.9';

        $this->assertTrue( sys_rate_limiter( $key, $limit, $interval ) );
        $this->assertIsArray( get_transient( $transient ) );

        delete_transient( $transient );
        unset( $_SERVER['HTTP_CLIENT_IP'] );
    }

    public function test_chat_queue() {
        $queue = new Sys_ChatQueue();
        $queue->enqueue('msg1');
        $queue->enqueue('msg2');
        $this->assertSame('msg1', $queue->dequeue());
        $this->assertSame('msg2', $queue->dequeue());
        $this->assertNull($queue->dequeue());
    }
}
