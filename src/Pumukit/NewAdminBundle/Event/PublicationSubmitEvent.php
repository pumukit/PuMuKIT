<?php

namespace Pumukit\NewAdminBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

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
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
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
