<?php
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order\Exceptions;

use Funeralzone\Calfords\Model\Order\PaymentAmount\PaymentAmount;
use Funeralzone\FAS\Common\Exceptions\AbstractDomainException;
use Funeralzone\FAS\Common\Exceptions\DomainException;

final class PaymentAmountMustBeGreaterThanZero extends AbstractDomainException implements DomainException
{
    public function __construct(PaymentAmount $amount)
    {
        parent::__construct(sprintf('The payment amount % is invalid. Payment amount should be greater than zero.', $amount->toNative()['amount']));
    }
}