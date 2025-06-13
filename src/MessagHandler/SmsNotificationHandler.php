<?php
use App\Message\SmsNotification;
use Symfony\Component\Messenger\MessageBusInterface;

class SomeController
{
    public function someAction(MessageBusInterface $bus)
    {
        $bus->dispatch(new SmsNotification('Hola mundo'));
    }
}
