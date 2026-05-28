<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Twig;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PumukitExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getPaellaLayout', [$this, 'getPaellaLayout', ['needs_environment' => true]]),
        ];
    }

    public function getPaellaLayout(MultimediaObject $mmobj, Request $request)
    {
        $paellaLayout = 'professor_slide';

        if ($mmobj->getProperty('opencastinvert')) {
            $paellaLayout = 'slide_professor';
        }

        if ($mmobj->getProperty('paellalayout')) {
            $paellaLayout = $mmobj->getProperty('paellalayout');
        }

        return $request->query->get('paella_layout', $paellaLayout);
    }
}
