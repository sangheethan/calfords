<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order;

use Funeralzone\Calfords\Model\Order\BusinessAddress\NonNullBusinessAddress;
use Funeralzone\Calfords\Model\Order\BusinessName\NonNullBusinessName;
use Funeralzone\Calfords\Model\Order\ContactPerson\NonNullContactPerson;
use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use Funeralzone\Calfords\Model\Order\Events\OrderWasPaid\OrderWasPaid;
use Funeralzone\Calfords\Model\Order\Events\PaymentWasReceived\PaymentWasReceived;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustBeGreaterThanZero;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustNotBeNegative;
use Funeralzone\Calfords\Model\Order\Exceptions\PaymentAmountMustBeGreaterThanZero;
use Funeralzone\Calfords\Model\Order\Exceptions\PaymentAmountMustNotBeNegative;
use Funeralzone\Calfords\Model\Order\Exceptions\PaymentAmountMustNotExceedTotalOrderAmount;
use Funeralzone\Calfords\Model\Order\OrderAmount\NonNullOrderAmount;
use Funeralzone\Calfords\Model\Order\OrderId\NonNullOrderId;
use Funeralzone\Calfords\Model\Order\PayeeName\NonNullPayeeName;
use Funeralzone\Calfords\Model\Order\PaymentAmount\NonNullPaymentAmount;
use Funeralzone\Calfords\Model\Order\PaymentStatus\NonNullPaymentStatus;
use Funeralzone\Calfords\Model\Order\PaymentType\NonNullPaymentType;
use Funeralzone\FAS\Common\AggregateTestingTrait;
use Funeralzone\FAS\DomainEntities\NonNullEntityId;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;


final class OrderTest extends TestCase
{
    use AggregateTestingTrait;
    private function getAggregateWithEvents(array $events): Order
    {
        /** @var Order $order */
        $order = $this->reconstituteAggregateFromHistory(Order::class, $events);
        return $order;
    }

    private function getAggregate($amount): Order
    {
        $id = Uuid::uuid4()->toString();
        /** @var Order $order */
        $order = $this->reconstituteAggregateFromHistory(
            Order::class,
            [
                OrderWasCreated::occur($id, [
                    'id' => $id,
                    'businessName' => "Jimmy's car rental",
                    'businessAddress' => [
                        'addressLine1' => "No 3. Exeter Road",
                        'addressLine2' => "",
                        'town' => "Exeter",
                        'county' => "Devon",
                        'postcode' => "EX2 4QE",
                        'countryCode' => "GB",
                    ],
                    'contactPerson' => "Jimmy Neutron",
                    'paymentStatus' => NonNullPaymentStatus::UNPAID()->toNative(),
                    'amount' => $amount,
                ]),
            ]
        );
        return $order;
    }

    public function test_order_amount_cannot_be_negative()
    {
        $this->expectException(OrderAmountMustNotBeNegative::class);
        $amount = -50;
        Order::create(
            NonNullOrderId::generate(),
            NonNullBusinessName::fromNative("Jimmy's car rental"),
            NonNullBusinessAddress::fromNative([
                'addressLine1' => "No 3. Exeter Road",
                'addressLine2' => "",
                'town' => "Exeter",
                'county' => "Devon",
                'postcode' => "EX2 4QE",
                'countryCode' => "GB",
            ]),
            NonNullContactPerson::fromNative("Jimmy Neutron"),
            NonNullPaymentStatus::UNPAID(),
            NonNullOrderAmount::fromNative([
                "amount" => $amount,
                "currency" => "gbp",
            ])
        );

    }

    public function test_order_amount_cannot_be_zero()
    {
        $this->expectException(OrderAmountMustBeGreaterThanZero::class);
        $amount = 0;
        Order::create(
            NonNullOrderId::generate(),
            NonNullBusinessName::fromNative("Jimmy's car rental"),
            NonNullBusinessAddress::fromNative([
                'addressLine1' => "No 3. Exeter Road",
                'addressLine2' => "",
                'town' => "Exeter",
                'county' => "Devon",
                'postcode' => "EX2 4QE",
                'countryCode' => "GB",
            ]),
            NonNullContactPerson::fromNative("Jimmy Neutron"),
            NonNullPaymentStatus::UNPAID(),
            NonNullOrderAmount::fromNative([
                "amount" => $amount,
                "currency" => "gbp",
            ])
        );
    }

    public function test_order_should_record_the_time_it_was_paid()
    {
        $order = $this->getAggregate([
            "amount" => "50",
            "currency" => "gbp",
        ]);
        $this->assertNull($order->getDatePaid()->toNative());
        $order->pay();
        $events = $this->popRecordedEvents($order);
        /** @var OrderWasPaid $event */
        $event = $events[0];
        $this->assertInstanceOf(OrderWasPaid::class, $event);
        $this->assertNotNull($order->getDatePaid()->toNative());
    }

    public function test_when_no_payments_have_been_made_that_the_payment_status_is_unpaid()
    {
        $order = $this->getAggregate([
            "amount" => "50",
            "currency" => "gbp",
        ]);
        $this->assertEquals(NonNullPaymentStatus::UNPAID(), $order->getPaymentStatus());
    }

    public function test_when_the_payments_total_has_not_reached_the_order_amount_the_payment_status_is_part_paid()
    {
        $id = Uuid::uuid4()->toString();
        $orderCreated = OrderWasCreated::occur($id, [
                'id' => $id,
                'businessName' => "Jimmy's car rental",
                'businessAddress' => [
                    'addressLine1' => "No 3. Exeter Road",
                    'addressLine2' => "",
                    'town' => "Exeter",
                    'county' => "Devon",
                    'postcode' => "EX2 4QE",
                    'countryCode' => "GB",
                ],
                'contactPerson' => "Jimmy Neutron",
                'paymentStatus' => NonNullPaymentStatus::UNPAID()->toNative(),
                'amount' => [
                    'amount' => 600,
                    'currency' => 'gbp'
                ],
            ]);
        $partPaymentReceived = PaymentWasReceived::occur($id, [
            'paymentId' => NonNullEntityId::generate()->toNative(),
            'payeeName' => 'Jonathan Benedict',
            'amount' => [
                'amount' => 150,
                'currency' => 'gbp'
            ],
            'type' => NonNullPaymentType::CARD_PAYMENT()->toNative()
        ]);
        $order = $this->getAggregateWithEvents([
            $orderCreated,
            $partPaymentReceived,
        ]);
        $this->assertEquals(NonNullPaymentStatus::PART_PAID(), $order->getPaymentStatus());
    }

    public function test_when_the_payments_total_has_reached_the_order_amount_the_payment_status_is_paid()
    {
        $id = Uuid::uuid4()->toString();
        $orderCreated = OrderWasCreated::occur($id, [
            'id' => $id,
            'businessName' => "Jimmy's car rental",
            'businessAddress' => [
                'addressLine1' => "No 3. Exeter Road",
                'addressLine2' => "",
                'town' => "Exeter",
                'county' => "Devon",
                'postcode' => "EX2 4QE",
                'countryCode' => "GB",
            ],
            'contactPerson' => "Jimmy Neutron",
            'paymentStatus' => NonNullPaymentStatus::UNPAID()->toNative(),
            'amount' => [
                'amount' => 600,
                'currency' => 'gbp'
            ],
        ]);
        $partPaymentReceived = PaymentWasReceived::occur($id, [
            'paymentId' => NonNullEntityId::generate()->toNative(),
            'payeeName' => 'Jonathan Benedict',
            'amount' => [
                'amount' => 150,
                'currency' => 'gbp'
            ],
            'type' => NonNullPaymentType::CARD_PAYMENT()->toNative()
        ]);
        $partPaymentReceived2 = PaymentWasReceived::occur($id, [
            'paymentId' => NonNullEntityId::generate()->toNative(),
            'payeeName' => 'Jonathan Benedict',
            'amount' => [
                'amount' => 150,
                'currency' => 'gbp'
            ],
            'type' => NonNullPaymentType::CARD_PAYMENT()->toNative()
        ]);
        $partPaymentReceived3 = PaymentWasReceived::occur($id, [
            'paymentId' => NonNullEntityId::generate()->toNative(),
            'payeeName' => 'Jonathan Benedict',
            'amount' => [
                'amount' => 300,
                'currency' => 'gbp'
            ],
            'type' => NonNullPaymentType::CARD_PAYMENT()->toNative()
        ]);
        $order = $this->getAggregateWithEvents([
            $orderCreated,
            $partPaymentReceived,
            $partPaymentReceived2,
            $partPaymentReceived3
        ]);
        $this->assertEquals(NonNullPaymentStatus::PAID(), $order->getPaymentStatus());
    }

    public function test_payment_amount_cannot_be_zero()
    {
        $this->expectException(PaymentAmountMustBeGreaterThanZero::class);
        $order = $this->getAggregate([
            "amount" => "50",
            "currency" => "gbp",
        ]);
        $order->receivePayment(
            NonNullEntityId::generate(),
            NonNullPayeeName::fromNative('Benedict Leonard'),
            NonNullPaymentAmount::fromNative([
                'amount' => 0,
                'currency' => 'gbp'
            ]),
            NonNullPaymentType::CARD_PAYMENT()
        );
    }

    public function test_payment_amount_cannot_be_negative()
    {
        $this->expectException(PaymentAmountMustNotBeNegative::class);
        $order = $this->getAggregate([
            "amount" => "50",
            "currency" => "gbp",
        ]);
        $order->receivePayment(
            NonNullEntityId::generate(),
            NonNullPayeeName::fromNative('Benedict Leonard'),
            NonNullPaymentAmount::fromNative([
                'amount' => -40,
                'currency' => 'gbp'
            ]),
            NonNullPaymentType::CARD_PAYMENT()
        );
    }

    public function test_total_payments_must_not_exceed_the_order_amount()
    {
        $this->expectException(PaymentAmountMustNotExceedTotalOrderAmount::class);
        $order = $this->getAggregate([
            "amount" => "50",
            "currency" => "gbp",
        ]);
        $order->receivePayment(
            NonNullEntityId::generate(),
            NonNullPayeeName::fromNative('Benedict Leonard'),
            NonNullPaymentAmount::fromNative([
                'amount' => 70,
                'currency' => 'gbp'
            ]),
            NonNullPaymentType::CARD_PAYMENT()
        );
    }

    public function test_total_amount_paid_on_an_order_is_calculated()
    {
        $order = $this->getAggregate([
            "amount" => "500",
            "currency" => "gbp",
        ]);
        $order->receivePayment(
            NonNullEntityId::generate(),
            NonNullPayeeName::fromNative('Benedict Leonard'),
            NonNullPaymentAmount::fromNative([
                'amount' => 70,
                'currency' => 'gbp'
            ]),
            NonNullPaymentType::CARD_PAYMENT()
        );
        $this->assertEquals(70, $order->getPaymentAmount()->getMoney()->getAmount());
        $order->receivePayment(
            NonNullEntityId::generate(),
            NonNullPayeeName::fromNative('Benedict Leonard'),
            NonNullPaymentAmount::fromNative([
                'amount' => 100,
                'currency' => 'gbp'
            ]),
            NonNullPaymentType::CARD_PAYMENT()
        );
        $this->assertEquals(170, $order->getPaymentAmount()->getMoney()->getAmount());
    }
}
