<?php

namespace App\Command;

use App\Message\SmsNotification;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:send-message',
    description: 'Send a menssage to RabbitMQ',
)]
class SendMessageCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bus->dispatch(new SmsNotification('Hello from Symfony'));
        $output->writeln('Message sent.');
        return Command::SUCCESS;
    }
}