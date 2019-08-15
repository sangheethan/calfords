<?php
namespace Funeralzone\Calfords\Model\Order\Exceptions;
use Funeralzone\Calfords\Model\Order\OrderAmount\OrderAmount;
use Funeralzone\FAS\Common\Exceptions\AbstractDomainException;
use Funeralzone\FAS\Common\Exceptions\DomainException;

final class OrderAmountMustBeGreaterThanZero extends AbstractDomainException implements DomainException
{
	public function __construct(OrderAmount $amount)
	{
		parent::__construct(sprintf('The order amount % is invalid. Order amount should be greater than zero.', $amount->toNative()['amount']));
	}
}