<?php
/**
 * Created by PhpStorm.
 * User: sang
 * Date: 15/08/2019
 * Time: 17:22
 */

namespace Funeralzone\Calfords\Model\Order\Exceptions;


use Funeralzone\Calfords\Model\Order\OrderAmount\OrderAmount;
use Funeralzone\FAS\Common\Exceptions\AbstractDomainException;
use Funeralzone\FAS\Common\Exceptions\DomainException;

final class OrderAmountMustNotBeNegative extends AbstractDomainException implements DomainException
{
    public function __construct(OrderAmount $amount)
    {
        parent::__construct(sprintf('The order amount % is invalid. Order amount should not be negative.', $amount->toNative()['amount']));
    }
}