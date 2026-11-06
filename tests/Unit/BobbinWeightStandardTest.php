<?php

namespace Tests\Unit;

use App\Support\BobbinWeightStandard;
use PHPUnit\Framework\TestCase;

class BobbinWeightStandardTest extends TestCase
{
    public function test_bobbin_weight_grades_follow_the_standard_intervals(): void
    {
        $this->assertSame('AX', BobbinWeightStandard::gradeFor(5350));
        $this->assertSame('AX', BobbinWeightStandard::gradeFor(5485));
        $this->assertSame('AX', BobbinWeightStandard::gradeFor(5600));
        $this->assertSame('AE', BobbinWeightStandard::gradeFor(5100));
        $this->assertSame('AE', BobbinWeightStandard::gradeFor(5349));
        $this->assertSame('A', BobbinWeightStandard::gradeFor(1000));
        $this->assertSame('A', BobbinWeightStandard::gradeFor(5099));
        $this->assertSame('B', BobbinWeightStandard::gradeFor(500));
        $this->assertSame('B', BobbinWeightStandard::gradeFor(999));
        $this->assertNull(BobbinWeightStandard::gradeFor(499.99));
        $this->assertNull(BobbinWeightStandard::gradeFor(5600.01));
    }

    public function test_ax_six_bobbin_box_gross_range_matches_the_standard(): void
    {
        $this->assertSame([
            'min' => 34.74,
            'max' => 36.25,
            'center' => 35.64,
        ], BobbinWeightStandard::grossRangeFor('AX', 6));

        $this->assertNull(BobbinWeightStandard::grossRangeFor('AE', 6));
        $this->assertNull(BobbinWeightStandard::grossRangeFor('AX', 5));
    }
}
