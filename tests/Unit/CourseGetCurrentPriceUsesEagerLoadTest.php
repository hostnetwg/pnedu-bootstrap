<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CoursePriceVariant;
use Tests\TestCase;

class CourseGetCurrentPriceUsesEagerLoadTest extends TestCase
{
    public function test_get_current_price_uses_loaded_price_variants_without_extra_query_shape(): void
    {
        $course = new Course;
        $course->forceFill([
            'end_date' => now()->addDay()->toDateTimeString(),
        ]);
        $course->id = 999001;
        $course->exists = true;

        $variant = new CoursePriceVariant;
        $variant->forceFill([
            'course_id' => 999001,
            'price' => 199.00,
            'is_active' => 1,
            'is_promotion' => 0,
            'name' => 'Standard',
        ]);
        $variant->id = 1;
        $variant->exists = true;

        $course->setRelation('priceVariants', collect([$variant]));

        $info = $course->getCurrentPrice();

        $this->assertNotNull($info);
        $this->assertSame(199.0, $info['price']);
        $this->assertSame(1, $info['price_variant_id']);
    }
}
