<?php
declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order\Exceptions;

use Funeralzone\Calfords\Model\Order\OrderAmount\OrderAmount;
use Funeralzone\Calfords\Model\Order\PaymentAmount\PaymentAmount;
use Funeralzone\FAS\Common\Exceptions\AbstractDomainException;
use Funeralzone\FAS\Common\Exceptions\DomainException;

final class PaymentAmountMustNotExceedTotalOrderAmount extends AbstractDomainException implements DomainException
{
    public function __construct(OrderAmount $orderAmount, PaymentAmount $amount)
    {
        parent::__construct(
            sprintf(
                'A payment of % will exceed the total order amount of %. Overpayment is not permitted',
                $amount->getMoney()->getAmount(),
                $orderAmount->getMoney()->getAmount()
            )
        );
    }
}
