<?php
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order\Exceptions;

use Funeralzone\Calfords\Model\Order\PaymentAmount\NonNullPaymentAmount;
use Funeralzone\FAS\Common\Exceptions\AbstractDomainException;
use Funeralzone\FAS\Common\Exceptions\DomainException;

final class PaymentAmountMustNotExceedTotalOrderAmount extends AbstractDomainException implements DomainException
{
    public function __construct(NonNullPaymentAmount $amount)
    {
        parent::__construct(sprintf('The payment amount of % has exceeded the total order amount.', $amount->toNative()['amount']));
    }
}