<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Create;

use Pumukit\SchemaBundle\Document\Live;

final class CreateChannelResponse
{
    public function __construct(public readonly Live $channel) {}
}

