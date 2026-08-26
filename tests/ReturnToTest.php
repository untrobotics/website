<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../template/url-safety.php';

/** URW-52: open-redirect guard for the login/register returnto param. */
final class ReturnToTest extends TestCase
{
    public function testAcceptsSameOriginRelativePath(): void
    {
        $this->assertSame('/merch', safe_returnto('/merch'));
        $this->assertSame('/me/', safe_returnto('/me/'));
        $this->assertSame('/merch/product/abc/def', safe_returnto('/merch/product/abc/def'));
    }

    public function testRejectsProtocolRelativeAndScheme(): void
    {
        $this->assertNull(safe_returnto('//evil.com'));
        $this->assertNull(safe_returnto('/\\evil.com'));      // backslash trick
        $this->assertNull(safe_returnto('https://evil.com'));
    }

    public function testRejectsAuthLoop(): void
    {
        $this->assertNull(safe_returnto('/auth/login'));
        $this->assertNull(safe_returnto('/auth'));
    }

    public function testRejectsControlCharsEmptyAndBareSlash(): void
    {
        $this->assertNull(safe_returnto("/foo\r\nSet-Cookie: x=1")); // header injection
        $this->assertNull(safe_returnto(''));
        $this->assertNull(safe_returnto('/'));   // bare slash: needs a non-slash after
        $this->assertNull(safe_returnto(null));
        $this->assertNull(safe_returnto(123));   // non-string
    }

    public function testReturntoQsEncodesValidTargets(): void
    {
        $this->assertSame('?returnto=%2Fmerch', returnto_qs('/merch'));
        $this->assertSame('?returnto=%2Fme%2F', returnto_qs('/me/'));
    }

    public function testReturntoQsDropsInvalidTargets(): void
    {
        $this->assertSame('', returnto_qs('//evil.com'));
        $this->assertSame('', returnto_qs('/auth/login'));
        $this->assertSame('', returnto_qs(''));
    }
}
