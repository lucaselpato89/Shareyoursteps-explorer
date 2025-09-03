<?php
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {
    public function test_haversine() {
        $distance = sys_haversine_km(0, 0, 0, 1); // ~111.32 km along equator
        $this->assertEqualsWithDelta(111.32, $distance, 0.5);
    }

    public function test_rate_limiter() {
        $key = 'test';
        $limit = 2;
        $interval = 60;
        $this->assertTrue(sys_rate_limiter($key, $limit, $interval));
        $this->assertTrue(sys_rate_limiter($key, $limit, $interval));
        $this->assertFalse(sys_rate_limiter($key, $limit, $interval));
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
