<?php

namespace Pumukit\Cmar\LiveBundle\Controller;

use Pumukit\Cmar\LiveBundle\Document\Message;
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
     * @return Response
     *
     * @Route("/show/{channel}", name="pumukit_cmar_live_chat_show", defaults={"channel" = "default"})
     * @Template
     */
    public function showAction($channel)
    {
        return array(
                     'enable_chat' => $this->container->getParameter('pumukit_cmar_live.chat.enable'),
                     'updateInterval' => $this->container->getParameter('pumukit_cmar_live.chat.update_interval'),
                     'channel' => $channel
                     );
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @Route("/post/{channel}", name="pumukit_cmar_live_chat_post", defaults={"channel" = "default"})
     */
    public function postAction(Request $request, $channel)
    {
        $message = new Message();
        $message->setAuthor($request->get('name'));
        $message->setChannel($channel);
        $message->setMessage($request->get('message'));
        $message->setInsertDate(new \DateTime());

        try {
            $dm = $this->get("doctrine_mongodb.odm.document_manager");
            $dm->persist($message);
            $dm->flush();
        } catch (\Exception $e) {

        }
        $response = array('message' => 'Successful');

        return new JsonResponse($response);
    }

    /**
     * @Route("/list/{channel}", name="pumukit_cmar_live_chat_list", defaults={"channel" = "default"})
     * @Template
     */
    public function listAction($channel)
    {
        $dm = $this->get("doctrine_mongodb.odm.document_manager");
        $repo = $dm->getRepository('PumukitCmarLiveBundle:Message');
        $messages = $repo->findBy(
                                  array('channel' => $channel),
                                  array('insert_date' => 'asc')
                                  );

        return array(
                     'messages' => $messages,
                     );
    }
}