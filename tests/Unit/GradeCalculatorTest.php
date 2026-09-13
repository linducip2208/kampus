<?php

namespace Tests\Unit;

use App\Services\Academic\GradeCalculator;
use Illuminate\Support\Collection;
use stdClass;
use Tests\TestCase;

class GradeCalculatorTest extends TestCase
{
    public function test_weighted_gpa_uses_credits(): void
    {
        $a = (object) ['credits' => 4, 'grade' => (object) ['gradeScale' => (object) ['grade_point' => 4]]];
        $b = (object) ['credits' => 2, 'grade' => (object) ['gradeScale' => (object) ['grade_point' => 3]]];
        $this->assertSame(3.67, (new GradeCalculator)->gpa(new Collection([$a, $b])));
    }
}
