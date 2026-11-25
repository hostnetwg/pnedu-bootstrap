<?php

namespace Tests\Unit;

use App\Support\PhoneDisplay;
use PHPUnit\Framework\TestCase;

class PhoneDisplayTest extends TestCase
{
    public function test_formats_polish_mobile_in_groups(): void
    {
        $this->assertSame('+48 501 654 274', PhoneDisplay::format('501654274'));
        $this->assertSame('+48 501 654 274', PhoneDisplay::format('501 654 274'));
        $this->assertSame('+48 501 654 274', PhoneDisplay::format('+48 501-654-274'));
        $this->assertSame('+48 501 654 274', PhoneDisplay::format('0048501654274'));
        $this->assertSame('tel:+48501654274', PhoneDisplay::telHref('501654274'));
    }

    public function test_leaves_unrecognized_numbers_unchanged(): void
    {
        $this->assertSame('12345', PhoneDisplay::format('12345'));
        $this->assertSame('', PhoneDisplay::format('  '));
        $this->assertNull(PhoneDisplay::telHref(''));
    }
}
