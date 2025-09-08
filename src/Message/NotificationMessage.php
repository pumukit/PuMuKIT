<?php

// src/Message/NotificationMessage.php
namespace App\Message;

class NotificationMessage
{
    public function __construct(
        private string $content,
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }
}