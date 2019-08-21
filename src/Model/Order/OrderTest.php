<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order;

use Funeralzone\Calfords\Model\Order\BusinessAddress\NonNullBusinessAddress;
use Funeralzone\Calfords\Model\Order\BusinessName\NonNullBusinessName;
use Funeralzone\Calfords\Model\Order\ContactPerson\NonNullContactPerson;
use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use Funeralzone\Calfords\Model\Order\Events\OrderWasPaid\OrderWasPaid;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustBeGreaterThanZero;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustNotBeNegative;
use Funeralzone\Calfords\Model\Order\OrderAmount\NonNullOrderAmount;
use Funeralzone\Calfords\Model\Order\OrderId\NonNullOrderId;
use Funeralzone\Calfords\Model\Order\PaymentStatus\NonNullPaymentStatus;
use Funeralzone\FAS\Common\AggregateTestingTrait;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;


final class OrderTest extends TestCase
{
    use AggregateTestingTrait;

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

    }

    public function test_when_the_payments_total_has_not_reached_the_order_amount_the_payment_status_is_part_paid()
    {

    }

    public function test_when_the_payments_total_has_reached_the_order_amount_the_payment_status_is_paid()
    {

    }

    public function test_payment_amount_cannot_be_zero()
    {

    }

    public function test_payment_amount_cannot_be_negative()
    {

    }

    public function test_total_payments_must_not_exceed_the_order_amount()
    {

    }

    public function test_total_amount_paid_on_an_order_is_calculated()
    {
    }
}
