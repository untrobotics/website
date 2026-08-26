<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../api/paypal/orders/paypal_api.php';

/** URW-208-adjacent: carrier normalization for PayPal shipment tracking. */
final class MapCarrierTest extends TestCase
{
    /** Invoke the private static PayPalOrdersAPI::map_carrier via reflection. */
    private function mapCarrier(string $carrier): array
    {
        $m = new ReflectionMethod(PayPalOrdersAPI::class, 'map_carrier');
        $m->setAccessible(true);
        return $m->invoke(null, $carrier);
    }

    public function testKnownCarriersMapToTheirEnum(): void
    {
        $this->assertSame(['USPS', ''], $this->mapCarrier('USPS'));
        $this->assertSame(['USPS', ''], $this->mapCarrier('usps priority mail'));
        $this->assertSame(['FEDEX', ''], $this->mapCarrier('FedEx'));
        $this->assertSame(['FEDEX', ''], $this->mapCarrier('FED EX Ground'));
        $this->assertSame(['UPS', ''], $this->mapCarrier('UPS Ground'));
        $this->assertSame(['DHL_EXPRESS', ''], $this->mapCarrier('DHL Express'));
    }

    public function testDhlWithoutExpressFallsBackToOther(): void
    {
        // "DHL eCommerce" contains DHL but not EXPRESS -> OTHER with the raw name.
        $this->assertSame(['OTHER', 'DHL eCommerce'], $this->mapCarrier('DHL eCommerce'));
    }

    public function testUnknownCarrierIsOtherWithRawName(): void
    {
        $this->assertSame(['OTHER', 'Royal Mail'], $this->mapCarrier('Royal Mail'));
        $this->assertSame(['OTHER', 'Canada Post'], $this->mapCarrier('Canada Post'));
    }

    public function testEmptyCarrierIsOtherWithEmptyName(): void
    {
        $this->assertSame(['OTHER', ''], $this->mapCarrier(''));
        $this->assertSame(['OTHER', ''], $this->mapCarrier('   '));
    }
}
