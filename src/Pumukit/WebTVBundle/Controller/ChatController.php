<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\Message;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/chat")
 */
class ChatController extends AbstractController
{
    protected $documentManager;
    protected $pumukitLiveChatEnable;
    protected $pumukitLiveChatUpdateInterval;

    public function __construct(DocumentManager $documentManager, bool $pumukitLiveChatEnable, int $pumukitLiveChatUpdateInterval)
    {
        $this->documentManager = $documentManager;
        $this->pumukitLiveChatEnable = $pumukitLiveChatEnable;
        $this->pumukitLiveChatUpdateInterval = $pumukitLiveChatUpdateInterval;
    }

    /**
     * @Route("/show/{id}", name="pumukit_live_chat_show")
     */
    public function showAction(Request $request, #[MapDocument(id: 'id')] MultimediaObject $multimediaObject): Response
    {
        $username = $this->getUser();
        if (!$username) {
            $sessionCookie = $request->cookies->get('PHPSESSID');
            $message = $this->documentManager->getRepository(Message::class)->findOneBy(['cookie' => $sessionCookie]);
            if ($message && ($author = $message->getAuthor())) {
                $username = $author;
            }
        }

        return $this->render('@PumukitWebTV/Live/Chat/show.html.twig', [
            'enable_chat' => $this->pumukitLiveChatEnable,
            'chatUpdateInterval' => $this->pumukitLiveChatUpdateInterval,
            'multimediaObject' => $multimediaObject,
            'username' => $username,
        ]);
    }

    /**
     * @Route("/basic/show/{id}", name="pumukit_live_chat_basic_show")
     */
    public function showBasicAction(Request $request, #[MapDocument(id: 'id')] Live $live): Response
    {
        $username = $this->getUser();
        if (!$username) {
            $sessionCookie = $request->cookies->get('PHPSESSID');
            $message = $this->documentManager->getRepository(Message::class)->findOneBy(['cookie' => $sessionCookie]);
            if ($message && ($author = $message->getAuthor())) {
                $username = $author;
            }
        }

        return $this->render('@PumukitWebTV/Live/Chat/basicLiveShow.html.twig', [
            'enable_chat' => $this->pumukitLiveChatEnable,
            'chatUpdateInterval' => $this->pumukitLiveChatUpdateInterval,
            'live' => $live,
            'username' => $username,
        ]);
    }

    /**
     * @Route("/post/{id}", name="pumukit_live_chat_post")
     */
    public function postAction(#[MapDocument(id: 'id')] MultimediaObject $multimediaObject, Request $request): JsonResponse
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
     * @Route("/basic/post/{id}", name="pumukit_live_chat_basic_post")
     */
    public function postBasicAction(#[MapDocument(id: 'id')] Live $live, Request $request): JsonResponse
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
     * @Route("/list/{id}", name="pumukit_live_chat_list")
     */
    public function listAction(#[MapDocument(id: 'id')] MultimediaObject $multimediaObject): Response
    {
        $messages = $this->documentManager->getRepository(Message::class)->findBy(
            ['multimediaObject' => $multimediaObject->getId()],
            ['insertDate' => 'asc']
        );

        return $this->render('@PumukitWebTV/Live/Chat/list.html.twig', [
            'messages' => $messages,
        ]);
    }

    /**
     * @Route("/basic/list/{id}", name="pumukit_live_chat_basic_list")
     */
    public function listBasicAction(#[MapDocument(id: 'id')] Live $live): Response
    {
        $messages = $this->documentManager->getRepository(Message::class)->findBy(
            ['channel' => $live->getId()],
            ['insertDate' => 'asc']
        );

        return $this->render('@PumukitWebTV/Live/Chat/list.html.twig', [
            'messages' => $messages,
        ]);
    }
}
