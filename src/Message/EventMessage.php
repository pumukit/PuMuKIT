<?php

// src/Message/EventMessage.php
namespace App\Message;

class EventMessage
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