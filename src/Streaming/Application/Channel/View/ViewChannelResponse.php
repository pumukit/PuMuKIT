<?php

declare(strict_types=1);

namespace App\Streaming\Application\Channel\View;

use Pumukit\SchemaBundle\Document\Live;

final class ViewChannelResponse
{
    public function __construct(public readonly Live $channel) {}
}

