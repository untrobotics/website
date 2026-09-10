<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../template/functions/functions.php';
require_once __DIR__ . '/../merch/includes/merch-data.php';

/**
 * Pure string/URL helpers used across the site: slug + accent stripping (used for
 * project/merch URLs), the duration humaniser, and the merch category normaliser
 * that must match a Printful "(Token)" suffix to a canonical category.
 */
final class HelpersTest extends TestCase
{
    public function testRemoveAccent(): void
    {
        $this->assertSame('Noel', remove_accent('Ñoël'));
        $this->assertSame('Creme Brulee', remove_accent('Crème Brûlée'));
        $this->assertSame('plain', remove_accent('plain'));
    }

    public function testPostSlug(): void
    {
        $this->assertSame('hello-world', post_slug('Hello World!'));
        $this->assertSame('cafe-del-mar', post_slug('Café del Mar'));
        $this->assertSame('unt-robotics-bomber-jacket', post_slug('UNT Robotics Bomber Jacket'));
    }

    public function testElapsedSecsToLargestUnit(): void
    {
        $this->assertSame('0 seconds', elapsed_secs_to_h(0));
        $this->assertSame('59 seconds', elapsed_secs_to_h(59));
        $this->assertSame('1 minute', elapsed_secs_to_h(60));
        $this->assertSame('1 minute', elapsed_secs_to_h(90));
        $this->assertSame('1 hour', elapsed_secs_to_h(3600));
        $this->assertSame('2 hours', elapsed_secs_to_h(7200));
        $this->assertSame('1 day', elapsed_secs_to_h(86400));
    }

    public function testMerchCategoryNormalisationMatchesSingularAndPlural(): void
    {
        // lower-case, strip non-alphanumerics, drop a trailing "s" — so a Printful
        // "(Hat)" suffix resolves to the canonical "Hats" category, etc.
        $this->assertSame(merch_norm_category('Hats'), merch_norm_category('Hat'));
        $this->assertSame('hat', merch_norm_category('Hats'));
        $this->assertSame(merch_norm_category('Shirts & Hoodies'), merch_norm_category('(Shirts & Hoodies)'));
        $this->assertSame('gear', merch_norm_category('Gear'));
    }
}
