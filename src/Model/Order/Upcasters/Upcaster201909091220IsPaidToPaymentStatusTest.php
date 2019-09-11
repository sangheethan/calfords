<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order\Upcasters;

use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\EventStore\Pdo\DefaultMessageConverter;

final class Upcaster201909091220IsPaidToPaymentStatusTest extends TestCase
{
    public function test_orders_created_with_isPaid_as_null_should_not_have_a_paymentStatus()
    {
        $messageFactory = new FQCNMessageFactory;
        $messageConverter = new DefaultMessageConverter;

        $message = $messageFactory->createMessageFromArray(OrderWasCreated::class, [
            'payload' => [],
        ]);

        $upcaster = new Upcaster201909091220IsPaidToPaymentStatus(
            $messageConverter,
            $messageFactory
        );

        $resultEvents = $upcaster->upcast($message);
        /* @var $resultMessage OrderWasCreated */
        $resultMessage = $resultEvents[0];

        $this->assertInstanceOf(OrderWasCreated::class, $resultMessage);
        $this->assertEquals([], $resultMessage->payload());
    }

    public function test_orders_created_with_isPaid_as_false_is_upcasted_to_paymentStatus_unpaid()
    {
        $payload = [
            'isPaid' => false,
        ];

        $expectedUpcastedPayload = [
            'paymentStatus' => 'UNPAID',
            'isPaid' => false,
        ];

        $messageFactory = new FQCNMessageFactory;
        $messageConverter = new DefaultMessageConverter;

        $message = $messageFactory->createMessageFromArray(OrderWasCreated::class, [
            'payload' => $payload,
        ]);

        $upcaster = new Upcaster201909091220IsPaidToPaymentStatus(
            $messageConverter,
            $messageFactory
        );

        $resultEvents = $upcaster->upcast($message);
        /* @var $resultMessage OrderWasCreated */
        $resultMessage = $resultEvents[0];

        $this->assertInstanceOf(OrderWasCreated::class, $resultMessage);
        $this->assertEquals($expectedUpcastedPayload, $resultMessage->payload());
    }

    public function test_orders_created_with_isPaid_as_true_is_upcasted_to_paymentStatus_paid()
    {
        $payload = [
            'isPaid' => true,
        ];

        $expectedUpcastedPayload = [
            'paymentStatus' => 'PAID',
            'isPaid' => true,
        ];

        $messageFactory = new FQCNMessageFactory;
        $messageConverter = new DefaultMessageConverter;

        $message = $messageFactory->createMessageFromArray(OrderWasCreated::class, [
            'payload' => $payload,
        ]);

        $upcaster = new Upcaster201909091220IsPaidToPaymentStatus(
            $messageConverter,
            $messageFactory
        );

        $resultEvents = $upcaster->upcast($message);
        /* @var $resultMessage OrderWasCreated */
        $resultMessage = $resultEvents[0];

        $this->assertInstanceOf(OrderWasCreated::class, $resultMessage);
        $this->assertEquals($expectedUpcastedPayload, $resultMessage->payload());
    }
}
