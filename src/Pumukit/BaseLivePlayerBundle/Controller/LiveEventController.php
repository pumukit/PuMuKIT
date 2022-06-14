<?php

declare(strict_types=1);

namespace Pumukit\BaseLivePlayerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

class LiveEventController
{
    /**
     * @Route("/livestream/rtmp://{extra}/{app}/{stream}")
     */
    public function publish(PublisherInterface $publisher, string $extra, string $app, string $stream): Response
    {
        $update = new Update(
            'https://livestream',
            json_encode(['extra' => $extra,
                'app' => $app,
                'stream' => $stream, ])
        );

        $publisher($update);

        return new Response($stream);
    }
}
