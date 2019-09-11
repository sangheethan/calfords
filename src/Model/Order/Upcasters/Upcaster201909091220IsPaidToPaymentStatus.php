<?php

declare(strict_types=1);

namespace Funeralzone\Calfords\Model\Order\Upcasters;

use Funeralzone\Calfords\Model\Order\Events\OrderWasCreated\OrderWasCreated;
use Funeralzone\Calfords\Model\Order\Events\OrderWasPaid\OrderWasPaid;
use Funeralzone\Calfords\Model\Order\PaymentStatus\PaymentStatus;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\Upcasting\SingleEventUpcaster;
use Prooph\EventStore\Upcasting\Upcaster;

final class Upcaster201909091220IsPaidToPaymentStatus extends SingleEventUpcaster implements Upcaster
{
    private $messageConverter;
    private $messageFactory;

    public function __construct(MessageConverter $messageConverter, MessageFactory $messageFactory)
    {
        $this->messageConverter = $messageConverter;
        $this->messageFactory = $messageFactory;
    }

    protected function canUpcast(Message $message): bool
    {
        $supportedMessages = [
            OrderWasCreated::class,
            OrderWasPaid::class,
        ];

        return in_array($message->messageName(), $supportedMessages);
    }

    protected function doUpcast(Message $message): array
    {
        $messageArray = $this->messageConverter->convertToArray($message);
        $isPaid = $messageArray['payload']['isPaid'] ?? null;
        if ($isPaid !== null) {
            $messageArray['payload']['paymentStatus'] = $isPaid ?
                PaymentStatus::PAID()->toNative() : PaymentStatus::UNPAID()->toNative();
        }

        return [
            $this->messageFactory->createMessageFromArray($message->messageName(), $messageArray)
        ];
    }
}
