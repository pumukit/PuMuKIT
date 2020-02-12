<?php

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\Message;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/chat")
 */
class ChatController extends AbstractController
{
    private $documentManager;
    private $pumukitLiveChatEnable;
    private $pumukitLiveChatUpdateInterval;

    public function __construct(DocumentManager $documentManager, bool $pumukitLiveChatEnable, int $pumukitLiveChatUpdateInterval)
    {
        $this->documentManager = $documentManager;
        $this->pumukitLiveChatEnable = $pumukitLiveChatEnable;
        $this->pumukitLiveChatUpdateInterval = $pumukitLiveChatUpdateInterval;
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Route("/show/{id}", name="pumukit_live_chat_show")
     * @Template("@PumukitWebTV/Live/Chat/show.html.twig")
     */
    public function showAction(Request $request, MultimediaObject $multimediaObject): array
    {
        $username = $this->getUser();
        if (!$username) {
            $sessionCookie = $request->cookies->get('PHPSESSID');
            $message = $this->documentManager->getRepository(Message::class)->findOneBy(['cookie' => $sessionCookie]);
            if ($message && ($author = $message->getAuthor())) {
                $username = $author;
            }
        }

        return [
            'enable_chat' => $this->pumukitLiveChatEnable,
            'chatUpdateInterval' => $this->pumukitLiveChatUpdateInterval,
            'multimediaObject' => $multimediaObject,
            'username' => $username,
        ];
    }

    /**
     * @ParamConverter("live", class="PumukitSchemaBundle:Live", options={"id" = "id"})
     * @Route("/basic/show/{id}", name="pumukit_live_chat_basic_show")
     * @Template("@PumukitWebTV/Live/Chat/basicLiveShow.html.twig")
     */
    public function showBasicAction(Request $request, Live $live): array
    {
        $username = $this->getUser();
        if (!$username) {
            $sessionCookie = $request->cookies->get('PHPSESSID');
            $message = $this->documentManager->getRepository(Message::class)->findOneBy(['cookie' => $sessionCookie]);
            if ($message && ($author = $message->getAuthor())) {
                $username = $author;
            }
        }

        return [
            'enable_chat' => $this->pumukitLiveChatEnable,
            'chatUpdateInterval' => $this->pumukitLiveChatUpdateInterval,
            'live' => $live,
            'username' => $username,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Route("/post/{id}", name="pumukit_live_chat_post")
     */
    public function postAction(MultimediaObject $multimediaObject, Request $request): JsonResponse
    {
        $message = new Message();
        $message->setAuthor($request->get('name'));
        $message->setMultimediaObject($multimediaObject);
        $message->setMessage($request->get('message'));
        $message->setInsertDate(new \DateTime());
        $sessionCookie = $request->cookies->get('PHPSESSID');
        $message->setCookie($sessionCookie);

        try {
            $this->documentManager->persist($message);
            $this->documentManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error'], 500);
        }

        return new JsonResponse(['message' => 'Successful']);
    }

    /**
     * @ParamConverter("live", class="PumukitSchemaBundle:Live", options={"id" = "id"})
     * @Route("/basic/post/{id}", name="pumukit_live_chat_basic_post")
     */
    public function postBasicAction(Live $live, Request $request): JsonResponse
    {
        $message = new Message();
        $message->setAuthor($request->get('name'));
        $message->setChannel($live->getId());
        $message->setMessage($request->get('message'));
        $message->setInsertDate(new \DateTime());
        $sessionCookie = $request->cookies->get('PHPSESSID');
        $message->setCookie($sessionCookie);

        try {
            $this->documentManager->persist($message);
            $this->documentManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error'], 500);
        }

        return new JsonResponse(['message' => 'Successful']);
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Route("/list/{id}", name="pumukit_live_chat_list")
     * @Template("@PumukitWebTV/Live/Chat/list.html.twig")
     */
    public function listAction(MultimediaObject $multimediaObject): array
    {
        $messages = $this->documentManager->getRepository(Message::class)->findBy(
            ['multimediaObject' => $multimediaObject->getId()],
            ['insertDate' => 'asc']
        );

        return [
            'messages' => $messages,
        ];
    }

    /**
     * @ParamConverter("live", class="PumukitSchemaBundle:Live", options={"id" = "id"})
     * @Route("/basic/list/{id}", name="pumukit_live_chat_basic_list")
     * @Template("@PumukitWebTV/Live/Chat/list.html.twig")
     */
    public function listBasicAction(Live $live): array
    {
        $messages = $this->documentManager->getRepository(Message::class)->findBy(
            ['channel' => $live->getId()],
            ['insertDate' => 'asc']
        );

        return [
            'messages' => $messages,
        ];
    }
}
