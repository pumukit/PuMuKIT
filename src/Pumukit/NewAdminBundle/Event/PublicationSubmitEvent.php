<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class PublicationSubmitEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var Request
     */
    protected $request;

    /**
     * PublicationSubmitEvent constructor.
     */
    public function __construct(MultimediaObject $multimediaObject, Request $request)
    {
        $this->multimediaObject = $multimediaObject;
        $this->request = $request;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
