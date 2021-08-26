<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use TusPhp\Tus\Server;

class TusService
{
    protected $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function getServer(): Server
    {
        return $this->server;
    }
}
