<?php
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order;

use Funeralzone\Calfords\Model\Order\BusinessAddress\NonNullBusinessAddress;
use Funeralzone\Calfords\Model\Order\BusinessName\NonNullBusinessName;
use Funeralzone\Calfords\Model\Order\ContactPerson\NonNullContactPerson;
use Funeralzone\Calfords\Model\Order\DatePaid\DatePaid;
use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use Funeralzone\Calfords\Model\Order\Events\OrderWasPaid\OrderWasPaid;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustBeGreaterThanZero;
use Funeralzone\Calfords\Model\Order\Exceptions\OrderAmountMustNotBeNegative;
use Funeralzone\Calfords\Model\Order\OrderAmount\NonNullOrderAmount;
use Funeralzone\Calfords\Model\Order\OrderId\NonNullOrderId;
use Funeralzone\Calfords\Model\Order\OrderIsPaid\NonNullOrderIsPaid;
use Funeralzone\FAS\FasApp\Prooph\ApplyDeltaTrait;
use Funeralzone\FAS\FasApp\Prooph\EventSourcing\SerialisableAggregateRoot;
use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\AggregateRoot;

final class Order extends AggregateRoot implements SerialisableAggregateRoot
{
    use ApplyDeltaTrait;
    /** @var  NonNullOrderId $id */
    private $id;
    /** @var  NonNullBusinessName $businessName */
    private $businessName;
    /** @var  NonNullBusinessAddress $businessAddress */
    private $businessAddress;
    /** @var  NonNullContactPerson $contactPerson */
    private $contactPerson;
    /** @var  NonNullOrderIsPaid $isPaid */
    private $isPaid;
    /** @var  NonNullOrderAmount $amount */
    private $amount;
    /** @var  DatePaid $datePaid */
    private $datePaid;

    protected function aggregateId(): string
    {
        return $this->id->toNative();
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

    public function getIsPaid(): NonNullOrderIsPaid
    {
        return $this->isPaid;
    }

    public function getAmount(): NonNullOrderAmount
    {
        return $this->amount;
    }

    public function getDatePaid(): DatePaid
    {
        return $this->datePaid;
    }

    /**
     * Apply given event
     */
    protected function apply(AggregateChanged $event): void
    {
        $className = get_class($event);
        $className = substr($className, strrpos($className, '\\') + 1);
        $method    = 'apply' . $className;
        $this->$method($event);

        return;
    }

    /**
     * @param array $native
     *
     * @return static
     */
    public static function fromNative(array $native): self
    {
        return new self();
    }

    public function toNative(): array
    {
        return [];
    }

    public static function create(
        NonNullOrderId $id,
        NonNullBusinessName $businessName,
        NonNullBusinessAddress $businessAddress,
        NonNullContactPerson $contactPerson,
        NonNullOrderIsPaid $isPaid,
        NonNullOrderAmount $amount
    ): Order {
        if ($amount->getMoney()->isZero()) {
            throw new OrderAmountMustBeGreaterThanZero($amount);
        }
        if ($amount->getMoney()->isNegative()) {
            throw new OrderAmountMustNotBeNegative($amount);
        }
        $instance = new self();
        $instance->recordThat(
            OrderWasCreated::occur($id->toNative(), [
                'businessName' => $businessName->toNative(),
                'businessAddress' => $businessAddress->toNative(),
                'contactPerson' => $contactPerson->toNative(),
                'isPaid' => $isPaid->toNative(),
                'amount' => $amount->toNative(),
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
        $this->isPaid = $event->getIsPaid();
        $this->amount = $event->getAmount();
        $this->datePaid = DatePaid::null();
    }

    public function pay(): void
    {
        $this->recordThat(
            OrderWasPaid::occur($this->getId()->toNative(), [
                'isPaid' => NonNullOrderIsPaid::true()
            ])
        );
    }

    private function applyOrderWasPaid(OrderWasPaid $event): void
    {
        $this->id = $event->getId();
        $this->isPaid = NonNullOrderIsPaid::true();
        $this->datePaid = DatePaid::fromNative($event->createdAt()->format(DATE_RFC3339));
    }
}