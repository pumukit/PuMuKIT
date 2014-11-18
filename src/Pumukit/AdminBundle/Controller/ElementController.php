<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class ElementController extends AdminController
{

    /**
     * Create new resource or just display the form.
     */
    public function createAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->createNew();
        //$form = $this->getForm($resource);

        //if ($request->isMethod('POST') && $form->bind($request)->isValid()) {
        if ($request->isMethod('POST')) {
            $event = $this->create($resource);
            if (!$event->isStopped()) {
                $this->setFlash('success', 'create');

                return $this->redirectTo($resource);
            }

            $this->setFlash($event->getMessageType(), $event->getMessage(), $event->getMessageParams());
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        $view = $this
            ->view()
            ->setTemplate($config->getTemplate('create.html'))
            ->setData(array(
                $config->getResourceName() => $resource,
                'form'                     => $form->createView()
            ))
        ;

        return $this->handleView($view);
    }



}