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
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    protected $documentManager;
    protected $pumukitLiveChatEnable;
    protected $pumukitLiveChatUpdateInterval;
    private RateLimiterFactoryInterface $chatMessageLimiter;

    public function __construct(DocumentManager $documentManager, bool $pumukitLiveChatEnable, int $pumukitLiveChatUpdateInterval, RateLimiterFactoryInterface $chatMessageLimiter)
    {
        $this->documentManager = $documentManager;
        $this->pumukitLiveChatEnable = $pumukitLiveChatEnable;
        $this->pumukitLiveChatUpdateInterval = $pumukitLiveChatUpdateInterval;
        $this->chatMessageLimiter = $chatMessageLimiter;
    }

    #[Route('/show/{id}', name: 'pumukit_live_chat_show')]
    public function showAction(Request $request, #[MapDocument(id: 'id')] MultimediaObject $multimediaObject): Response
    {
        $username = $this->getUser() ?? $this->previousAuthor($request);

        return $this->render('@PumukitWebTV/Live/Chat/show.html.twig', [
            'enable_chat' => $this->pumukitLiveChatEnable,
            'chatUpdateInterval' => $this->pumukitLiveChatUpdateInterval,
            'multimediaObject' => $multimediaObject,
            'username' => $username,
        ]);
    }

    #[Route('/basic/show/{id}', name: 'pumukit_live_chat_basic_show')]
    public function showBasicAction(Request $request, #[MapDocument(id: 'id')] Live $live): Response
    {
        $username = $this->getUser() ?? $this->previousAuthor($request);

        return $this->render('@PumukitWebTV/Live/Chat/basicLiveShow.html.twig', [
            'enable_chat' => $this->pumukitLiveChatEnable,
            'chatUpdateInterval' => $this->pumukitLiveChatUpdateInterval,
            'live' => $live,
            'username' => $username,
        ]);
    }

    #[Route('/post/{id}', name: 'pumukit_live_chat_post', methods: ['POST'])]
    public function postAction(#[MapDocument(id: 'id')] MultimediaObject $multimediaObject, Request $request): JsonResponse
    {
        $message = new Message();
        $message->setMultimediaObject($multimediaObject);

        return $this->saveMessage($request, $message);
    }

    #[Route('/basic/post/{id}', name: 'pumukit_live_chat_basic_post', methods: ['POST'])]
    public function postBasicAction(#[MapDocument(id: 'id')] Live $live, Request $request): JsonResponse
    {
        $message = new Message();
        $message->setChannel($live->getId());

        return $this->saveMessage($request, $message);
    }

    #[Route('/list/{id}', name: 'pumukit_live_chat_list')]
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

    #[Route('/basic/list/{id}', name: 'pumukit_live_chat_basic_list')]
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

    private function saveMessage(Request $request, Message $message): JsonResponse
    {
        if (!$this->pumukitLiveChatEnable) {
            throw $this->createNotFoundException();
        }

        if (!$this->chatMessageLimiter->create($request->getClientIp())->consume()->isAccepted()) {
            return new JsonResponse(['message' => 'Too many messages'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $session = $request->getSession();
        $session->start();

        $message->setAuthor($request->request->get('name'));
        $message->setMessage($request->request->get('message'));
        $message->setInsertDate(new \DateTime());
        $message->setCookie(hash('sha256', $session->getId()));

        try {
            $this->documentManager->persist($message);
            $this->documentManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error'], 500);
        }

        return new JsonResponse(['message' => 'Successful']);
    }

    private function previousAuthor(Request $request): ?string
    {
        if (!$request->hasPreviousSession()) {
            return null;
        }

        $message = $this->documentManager->getRepository(Message::class)->findOneBy(['cookie' => hash('sha256', $request->getSession()->getId())]);

        return $message?->getAuthor() ?: null;
    }
}
