<?php
/**
 * Utility functions for Share Your Steps plugin.
 */

/**
 * Calculate distance between two coordinates using the Haversine formula.
 *
 * @param float $lat1 Latitude of first point in degrees.
 * @param float $lon1 Longitude of first point in degrees.
 * @param float $lat2 Latitude of second point in degrees.
 * @param float $lon2 Longitude of second point in degrees.
 * @return float Distance in kilometers.
 */
function sys_haversine_km( float $lat1, float $lon1, float $lat2, float $lon2 ): float {
    $earth_radius = 6371; // Earth's radius in kilometers.

    $dLat = deg2rad( $lat2 - $lat1 );
    $dLon = deg2rad( $lon2 - $lon1 );

    $a = sin( $dLat / 2 ) * sin( $dLat / 2 ) +
         cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
         sin( $dLon / 2 ) * sin( $dLon / 2 );

    $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

    return $earth_radius * $c;
}

/**
 * Rate limiter leveraging WordPress transients.
 *
 * Uses the current user's ID if available or falls back to the request IP
 * address to create a unique transient key. Expired keys are removed based on
 * the provided interval.
 *
 * @param string $key      Identifier for the action being rate limited.
 * @param int    $limit    Maximum number of allowed calls within the interval.
 * @param int    $interval Interval window in seconds.
 *
 * @return bool True if allowed, false if rate limited.
 */
function sys_rate_limiter( string $key, int $limit = 5, int $interval = 60 ): bool {
    $user_id   = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
    $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $identity  = $user_id > 0 ? 'user_' . $user_id : 'ip_' . $ip;
    $transient = 'sys_rate_' . $key . '_' . $identity;
    $now       = time();

    $timestamps = get_transient( $transient );
    if ( ! is_array( $timestamps ) ) {
        $timestamps = [];
    }

    // Remove timestamps outside the interval window.
    $timestamps = array_filter(
        $timestamps,
        static fn( $timestamp ) => ( $now - $timestamp ) < $interval
    );

    if ( empty( $timestamps ) ) {
        delete_transient( $transient );
    }

    if ( count( $timestamps ) >= $limit ) {
        // Persist the filtered list for subsequent calls.
        set_transient( $transient, $timestamps, $interval );
        return false;
    }

    $timestamps[] = $now;
    set_transient( $transient, $timestamps, $interval );
    return true;
}

/**
 * Basic FIFO chat message queue.
 */
class Sys_ChatQueue {
    /** @var \SplQueue */
    protected $queue;

    public function __construct() {
        $this->queue = new \SplQueue();
    }

    /**
     * Add a message to the queue.
     *
     * @param string $message Message to enqueue.
     */
    public function enqueue( string $message ): void {
        $this->queue->enqueue( $message );
    }

    /**
     * Remove the next message from the queue.
     *
     * @return string|null The next message or null if empty.
     */
    public function dequeue(): ?string {
        if ( $this->queue->isEmpty() ) {
            return null;
        }

        return $this->queue->dequeue();
    }

    /**
     * Get current queue size.
     *
     * @return int Number of messages in queue.
     */
    public function size(): int {
        return $this->queue->count();
    }
}
