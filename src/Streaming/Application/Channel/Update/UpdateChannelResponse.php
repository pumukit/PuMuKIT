<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\Update;

use Pumukit\SchemaBundle\Document\Live;

final class UpdateChannelResponse
{
    public function __construct(public readonly Live $channel) {}
}
