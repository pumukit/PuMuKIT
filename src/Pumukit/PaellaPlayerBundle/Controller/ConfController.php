<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\CaptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfController extends AbstractController
{
    private $documentManager;
    private $paellaXAPIEndpoint;
    private $paellaXAPIAuth;
    private $paellaAccessControlClass;
    private $paellaFootPrints;
    private $captionService;

    public function __construct(
        DocumentManager $documentManager,
        CaptionService $captionService,
        $paellaXAPIEndpoint,
        $paellaXAPIAuth,
        $paellaAccessControlClass,
        $paellaFootPrints
    ) {
        $this->documentManager = $documentManager;
        $this->captionService = $captionService;
        $this->paellaXAPIEndpoint = $paellaXAPIEndpoint;
        $this->paellaXAPIAuth = $paellaXAPIAuth;
        $this->paellaAccessControlClass = $paellaAccessControlClass;
        $this->paellaFootPrints = $paellaFootPrints;
    }

    #[Route('/paella/config.json', name: 'paella_player_config')]
    public function confAction(Request $request): Response
    {
        $configId = $request->query->getString('configID');
        if ('' === $configId) {
            throw $this->createNotFoundException();
        }

        $multimediaObject = $this->getMultimediaObject($configId);

        if ($multimediaObject instanceof MultimediaObject) {
            $jsonData = $this->renderView(
                '@PumukitPaellaPlayer/Conf/conf.json.twig',
                [
                    'xapi_endpoint' => $this->paellaXAPIEndpoint,
                    'xapi_auth' => $this->paellaXAPIAuth,
                    'access_control_class' => $this->paellaAccessControlClass,
                    'footprints' => $this->paellaFootPrints,
                    'isMonostream' => !$multimediaObject->isMultistream(),
                    'isMultistream' => $multimediaObject->isMultistream(),
                    'isLive' => $multimediaObject->isLive(),
                    'notLive' => !$multimediaObject->isLive(),
                    'hasCaptions' => $this->captionService->hasCaptions($multimediaObject),
                ]
            );

            return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
        }

        $live = preg_match('/^[0-9a-f]{24}$/', $configId)
            ? $this->documentManager->getRepository(Live::class)->findOneBy(['_id' => new ObjectId($configId)])
            : null;

        if ($live instanceof Live) {
            $jsonData = $this->renderView(
                '@PumukitPaellaPlayer/Conf/conf.json.twig',
                [
                    'xapi_endpoint' => $this->paellaXAPIEndpoint,
                    'xapi_auth' => $this->paellaXAPIAuth,
                    'access_control_class' => $this->paellaAccessControlClass,
                    'footprints' => $this->paellaFootPrints,
                    'isMonostream' => true,
                    'isMultistream' => false,
                    'isLive' => true,
                    'notLive' => false,
                    'hasCaptions' => false,
                ]
            );

            return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
        }

        throw $this->createNotFoundException();
    }

    private function getMultimediaObject(string $objectId)
    {
        $repository = $this->documentManager->getRepository(MultimediaObject::class);

        try {
            $objectIdObj = new ObjectId($objectId);

            $multimediaObject = $repository->findOneBy(['_id' => $objectIdObj]);
            if ($multimediaObject) {
                return $multimediaObject;
            }
        } catch (\Exception $e) {
            $multimediaObject = $repository->findOneBy(['numerical_id' => $objectId]);
            if (!$multimediaObject) {
                $multimediaObject = $repository->createQueryBuilder()
                    ->field('properties.pumukit1id')->equals($objectId)
                    ->getQuery()->getSingleResult() ?? null
                ;
            }

            if ($multimediaObject) {
                return $multimediaObject;
            }
        }

        return $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['secret' => $objectId]);
    }
}
