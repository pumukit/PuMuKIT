<?php

namespace Pumukit\LiveBundle\Controller;

use Pumukit\LiveBundle\Document\Message;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/chat")
 */
class ChatController extends Controller
{
    /**
     * Show chat.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Route("/show/{id}", name="pumukit_live_chat_show")
     * @Template
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return Response
     */
    public function showAction(MultimediaObject $multimediaObject, Request $request)
    {
        $username = $this->getUser();
        if (!$username) {
            $sessionCookie = $request->cookies->get('PHPSESSID');
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $messageRepo = $dm->getRepository('PumukitLiveBundle:Message');
            $message = $messageRepo->findOneBy(array('cookie' => $sessionCookie));
            if ($message && ($author = $message->getAuthor())) {
                $username = $author;
            }
        }
        return array(
            'enable_chat' => $this->container->getParameter('pumukit_live.chat.enable'),
            'chatUpdateInterval' => $this->container->getParameter('pumukit_live.chat.update_interval'),
            'multimediaObject' => $multimediaObject,
            'username' => $username,
        );
    }

    /**
     * Post message.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Route("/post/{id}", name="pumukit_live_chat_post")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return RedirectResponse
     */
    public function postAction(MultimediaObject $multimediaObject, Request $request)
    {
        $message = new Message();
        $message->setAuthor($request->get('name'));
        $message->setMultimediaObject($multimediaObject);
        $message->setMessage($request->get('message'));
        $message->setInsertDate(new \DateTime());
        $sessionCookie = $request->cookies->get('PHPSESSID');
        $message->setCookie($sessionCookie);

        try {
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $dm->persist($message);
            $dm->flush();
        } catch (\Exception $e) {
            return new JsonResponse(array('message' => 'Error'), 500);
        }

        return new JsonResponse(array('message' => 'Successful'));
    }

    /**
     * List messages.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Route("/list/{id}", name="pumukit_live_chat_list")
     * @Template
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return Response
     */
    public function listAction(MultimediaObject $multimediaObject, Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitLiveBundle:Message');
        $messages = $repo->findBy(
            array('multimediaObject' => $multimediaObject->getId()),
            array('insertDate' => 'asc')
        );

        return array(
            'messages' => $messages,
        );
    }
}
