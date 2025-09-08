<?php

// src/Message/WaitingMessage.php
namespace App\Message;

class WaitingMessage
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