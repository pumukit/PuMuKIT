<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TextIndexService;

class MultimediaObjectListener
{
    private DocumentManager $dm;
    private TextIndexService $textIndexService;

    public function __construct(DocumentManager $dm, TextIndexService $textIndexService)
    {
        $this->dm = $dm;
        $this->textIndexService = $textIndexService;
    }

    public function postUpdate($event): void
    {
        $multimediaObject = $event->getMultimediaObject();
        $this->updateTextIndex($multimediaObject);
        $this->dm->flush();
    }

    public function updateTextIndex(MultimediaObject $multimediaObject): void
    {
        $this->textIndexService->updateMultimediaObjectTextIndex($multimediaObject);
    }
}
