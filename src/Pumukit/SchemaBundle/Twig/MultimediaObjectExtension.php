<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MultimediaObjectExtension extends AbstractExtension
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('multimedia_object_from_id', [$this, 'getMultimediaObjectFromId']),
        ];
    }

    public function getMultimediaObjectFromId($multimediaObjectId): ?MultimediaObject
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'id' => new ObjectId($multimediaObjectId),
        ]);
    }
}
