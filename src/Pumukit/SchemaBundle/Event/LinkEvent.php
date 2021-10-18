<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\Event;

class LinkEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var Link
     */
    protected $link;

    public function __construct(MultimediaObject $multimediaObject, Link $link)
    {
        $this->multimediaObject = $multimediaObject;
        $this->link = $link;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return Link
     */
    public function getLink()
    {
        return $this->link;
    }
}
