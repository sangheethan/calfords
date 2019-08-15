<?php
declare(strict_types=1);

namespace Funeralzone\calfords\Model\Order;

use Funeralzone\Calfords\Model\Order\BusinessAddress\NonNullBusinessAddress;
use Funeralzone\Calfords\Model\Order\BusinessName\NonNullBusinessName;
use Funeralzone\Calfords\Model\Order\ContactPerson\NonNullContactPerson;
use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use Funeralzone\Calfords\Model\Order\OrderAmount\NonNullOrderAmount;
use Funeralzone\Calfords\Model\Order\OrderHasPaid\NonNullOrderHasPaid;
use Funeralzone\Calfords\Model\Order\OrderId\NonNullOrderId;
use Funeralzone\FAS\FasApp\Prooph\ApplyDeltaTrait;
use Funeralzone\FAS\FasApp\Prooph\EventSourcing\SerialisableAggregateRoot;
use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\AggregateRoot;

class Order extends AggregateRoot implements SerialisableAggregateRoot
{
	use ApplyDeltaTrait;
	private $id;
	private $businessName;
	private $businessAddress;
	private $contactPerson;
	private $hasPaid;
	private $amount;

	protected function aggregateId(): string
	{
		return $this->id;
	}

	public function getId(): NonNullOrderId
	{
		return $this->id;
	}

	public function getBusinessName(): NonNullBusinessName
	{
		return $this->businessName;
	}

	public function getBusinessAddress(): NonNullBusinessAddress
	{
		return $this->businessAddress;
	}

	public function getContactPerson(): NonNullContactPerson
	{
		return $this->contactPerson;
	}

	public function getHasPaid(): NonNullOrderHasPaid
	{
		return $this->hasPaid;
	}

	public function getAmount(): NonNullOrderAmount
	{
		return $this->amount;
	}

	/**
	 * Apply given event
	 */
	protected function apply(AggregateChanged $event): void
	{
		$className = get_class($event);
		$className = substr($className, strrpos($className, '\\') + 1);
		$method = 'apply' . $className;
		$this->$method($event);

		return;
	}

	/**
	 * @param array $native
	 *
	 * @return static
	 */
	public static function fromNative(array $native)
	{
		// TODO: Implement fromNative() method.
	}

	public function toNative(): array
	{
		// TODO: Implement toNative() method.
	}

	public static function create(
		$id,
		$businessName,
		$businessAddress,
		$contactPerson,
		$hasPaid,
		$amount
	): Order
	{
		$instance = new self();
		$instance->recordThat(
			OrderWasCreated::occur($id->toNative(), [
				'businessName' => $businessName,
				'businessAddress' => $businessAddress,
				'contactPerson' => $contactPerson,
				'hasPaid' => $hasPaid,
				'amount' => $amount
			])
		);
		return $instance;
	}

	private function applyOrderWasCreated(OrderWasCreated $event): void
	{
		$this->id = $event->getId();
		$this->businessName = $event->getBusinessName();
		$this->businessAddress = $event->getBusinessAddress();
		$this->contactPerson = $event->getContactPerson();
		$this->hasPaid = $event->getHasPaid();
		$this->amount = $event->getAmount();
	}
}