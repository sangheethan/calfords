<?php

declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order;

use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use Funeralzone\Calfords\Model\Order\OrderId\NonNullOrderId;
use Funeralzone\FAS\Common\AggregateTestingTrait;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class OrderTest extends TestCase
{
	use AggregateTestingTrait;
	private function getAggregate($amount): Order
	{
		$id = Uuid::uuid4()->toString();
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
    public function test_order_amount_not_negative()
	{
		$amount = new Money(50, new Currency('gbp'));
		$amount->negative();
		$order = Order::create(
			NonNullOrderId::generate(),
			"Jimmy's car rental",
			[
				'addressLine1' => "No 3. Exeter Road",
				'addressLine2' => "",
				'town' => "Exeter",
				'county' => "Devon",
				'postcode' => "EX2 4QE",
				'countryCode' => "GB"
			],
			"Jimmy Neutron",
			true,
			[
				"amount" => -50,
				"currency" => "gbp"
			]
		);
		$events = $this->popRecordedEvents($order);
		$event = $events[0];
		$this->assertInstanceOf(OrderWasCreated::class, $event);
		$this->assertTrue($event->getAmount()->getMoney()->isNegative());
    }

    public function test_order_amount_cannot_be_zero()
	{
		$order = $this->getAggregate([
			"amount" => 0,
			"currency" => "gbp"
		]);
		$this->assertTrue($order->getAmount()->getMoney()->isZero());
    }
}
