<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\NewAdminBundle\Form\Type\UserUpdateProfileType;
use Pumukit\NewAdminBundle\Services\UserStatsService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\SeriesService;
use Pumukit\SchemaBundle\Services\UpdateUserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/user_profile")
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UserProfileController extends AbstractController
{
    protected $documentManager;
    protected $translator;
    protected $updateUserService;
    protected $userStatsService;
    protected $seriesService;
    protected $personService;

    public function __construct(
        DocumentManager $documentManager,
        UpdateUserService $updateUserService,
        TranslatorInterface $translator,
        UserStatsService $userStatsService,
        SeriesService $seriesService,
        PersonService $personService
    ) {
        $this->documentManager = $documentManager;
        $this->translator = $translator;
        $this->updateUserService = $updateUserService;
        $this->userStatsService = $userStatsService;
        $this->seriesService = $seriesService;
        $this->personService = $personService;
    }

    /**
     * @Route("/", name="pumukitnewadmin_profile_user_index")
     * @Template("@PumukitNewAdmin/UserProfile/template.html.twig")
     */
    public function profileAction(Request $request): array
    {
        $user = $this->getUser();

        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();

        $locale = $request->getLocale();
        $form = $this->createForm(UserUpdateProfileType::class, $user, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $user->isLocal()) {
            $this->updateUserService->update($user);
        }

        $seriesOfUser = $this->seriesService->getSeriesOfUser($user, false, $personalScopeRoleCode, ['public_date' => 'desc']);

        return [
            'user' => $user,
            'form' => $form->createView(),
            'seriesOfUser' => $seriesOfUser,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/UserProfile/template_user_stats.html.twig")
     */
    public function userStatsAction(): array
    {
        $objectsByStatus = $this->userStatsService->getUserMultimediaObjectsGroupByStats($this->getUser());
        $objectsByRole = $this->userStatsService->getUserMultimediaObjectsGroupByRole($this->getUser());
        $userStorage = $this->userStatsService->getUserStorageMB($this->getUser());
        $userDuration = $this->userStatsService->getUserUploadedHours($this->getUser());

        return [
            'objectsByStatus' => $objectsByStatus,
            'objectsByRole' => $objectsByRole,
            'userStorage' => $userStorage,
            'userDuration' => $userDuration,
        ];
    }

    /**
     * @Route("/check/email/{updateEmail}", name="pumukitnewadmin_profile_user_check_user")
     */
    public function checkEmailToUseOnUser(string $updateEmail): JsonResponse
    {
        $user = $this->documentManager->getRepository(User::class)->findOneBy([
            'email' => $updateEmail,
            'username' => ['$ne' => $this->getUser()->getUsername()],
        ]);

        $jsonResponseStatus = -1;
        $message = $this->translator->trans('Email already in use');
        if (!$user || $this->getUser()->getEmail() === $updateEmail) {
            $jsonResponseStatus = 0;
            $message = $this->translator->trans('User info updated');
        }

        return new JsonResponse(['status' => $jsonResponseStatus, 'message' => $message]);
    }
}
