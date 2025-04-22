<?php

namespace App\Tests\Entity;

use App\Entity\Payment;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    public function testPaymentEntity(): void
    {
        $payment = new Payment();
        $payment->setOrderId(123);
        $payment->setAmount(59.99);
        $payment->setPaymentStatus('paid');
        $payment->setCreatedAt(new \DateTimeImmutable('2025-04-21 18:00:00'));

        $this->assertSame(123, $payment->getOrderId());
        $this->assertSame(59.99, $payment->getAmount());
        $this->assertSame('paid', $payment->getPaymentStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $payment->getCreatedAt());
        $this->assertSame('2025-04-21 18:00:00', $payment->getCreatedAt()->format('Y-m-d H:i:s'));
    }
}
