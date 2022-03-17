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
     * @Route("/livestream/rtmp://{extra}/{app}/{stream}/{status}")
     */
    public function publish(PublisherInterface $publisher, string $extra, string $app, string $stream, string $status): Response
    {
        $update = new Update(
            // 'https://wowza-10-10-18-11.nip.io/'.$app."/".$stream,
            'https://hola',
            json_encode(['status' => 'up'])
        );

        // $update = new Update(
        //     // 'https://wowza-10-10-18-11.nip.io/'.$app."/".$stream,
        //     'https://'.$app.'/'.$stream,
        //     json_encode(['status' => $status])
        // );
        echo $extra;
        echo $app;
        echo $stream;
        echo $status;

        $publisher($update);

        return new Response($status);
    }
}
