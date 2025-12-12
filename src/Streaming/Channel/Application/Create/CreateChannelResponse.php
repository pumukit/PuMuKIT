<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Application\Create;

use Pumukit\SchemaBundle\Document\Live;

final class CreateChannelResponse
{
    public function __construct(public Live $channel) {}
}
