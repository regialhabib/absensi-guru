<?php

namespace Tests\Feature\Attendance;

use App\Services\Attendance\GeoFence;
use PHPUnit\Framework\TestCase;

class GeoFenceTest extends TestCase
{
    public function test_same_point_has_zero_distance(): void
    {
        $this->assertEqualsWithDelta(0.0, (new GeoFence)->distanceInMeters(-7.72, 113.49, -7.72, 113.49), 0.001);
    }

    public function test_one_degree_of_latitude_is_about_111_kilometers(): void
    {
        $this->assertEqualsWithDelta(111195, (new GeoFence)->distanceInMeters(0, 100, 1, 100), 50);
    }

    public function test_distance_is_symmetric(): void
    {
        $geoFence = new GeoFence;

        $this->assertEqualsWithDelta(
            $geoFence->distanceInMeters(-7.72, 113.49, -7.73, 113.50),
            $geoFence->distanceInMeters(-7.73, 113.50, -7.72, 113.49),
            0.001,
        );
    }
}
