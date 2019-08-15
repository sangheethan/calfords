<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order;

use Funeralzone\Calfords\Model\Order\BusinessAddress\BusinessAddress;
use Funeralzone\Calfords\Model\Order\BusinessName\BusinessName;
use Funeralzone\Calfords\Model\Order\ContactPerson\ContactPerson;
use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustBeGreaterThanZero;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustNotBeNegative;
use Funeralzone\Calfords\Model\Order\OrderAmount\OrderAmount;
use Funeralzone\Calfords\Model\Order\OrderHasPaid\OrderHasPaid;
use Funeralzone\Calfords\Model\Order\OrderId\OrderId;
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
					'businessName' =>"Jimmy's car rental",
					'businessAddress' =>[
						'addressLine1' => "No 3. Exeter Road",
						'addressLine2' => "",
						'town' => "Exeter",
						'county' => "Devon",
						'postcode' => "EX2 4QE",
						'countryCode' => "GB"
					],
					'contactPerson' => "Jimmy Neutron",
					'hasPaid' => true,
					'amount' => $amount
				]),
			]
		);
		return $order;
	}
	private function createOrder($amount)
    {
        Order::create(
            OrderId::generate(),
            BusinessName::fromNative("Jimmy's car rental"),
            BusinessAddress::fromNative([
                'addressLine1' => "No 3. Exeter Road",
                'addressLine2' => "",
                'town' => "Exeter",
                'county' => "Devon",
                'postcode' => "EX2 4QE",
                'countryCode' => "GB"
            ]),
            ContactPerson::fromNative("Jimmy Neutron"),
            OrderHasPaid::true(),
            OrderAmount::fromNative([
                "amount" => $amount,
                "currency" => "gbp"
            ])
        );
    }
    public function test_order_amount_cannot_be_negative()
	{
        $this->expectException(OrderAmountMustNotBeNegative::class);
		$this->createOrder(-50);
    }

    public function test_order_amount_cannot_be_zero()
	{
        $this->expectException(OrderAmountMustBeGreaterThanZero::class);
        $this->createOrder(0);
    }
}
