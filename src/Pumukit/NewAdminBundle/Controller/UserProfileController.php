<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Form\Type\UserUpdateProfileType;
use Pumukit\NewAdminBundle\Services\UserStatsService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\UpdateUserService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user_profile')]
#[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
class UserProfileController extends AbstractController
{
    protected $translator;
    protected $updateUserService;
    protected $userStatsService;

    public function __construct(
        UpdateUserService $updateUserService,
        TranslatorInterface $translator,
        UserStatsService $userStatsService
    ) {
        $this->translator = $translator;
        $this->updateUserService = $updateUserService;
        $this->userStatsService = $userStatsService;
    }

    #[Route('/', name: 'pumukitnewadmin_profile_user_index')]
    #[Template('@PumukitNewAdmin/UserProfile/template.html.twig')]
    public function profileAction(Request $request): array
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $locale = $request->getLocale();
        $form = $this->createForm(UserUpdateProfileType::class, $user, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $user->isLocal()) {
            $this->updateUserService->update($user);
        }

        return [
            'user' => $user,
            'form' => $form->createView(),
        ];
    }

    #[Template('@PumukitNewAdmin/UserProfile/template_user_stats.html.twig')]
    public function userStatsAction(): array
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getPerson()) {
            return ['error' => $this->translator->trans('Your user has no associated person, so its stats cannot be shown.')];
        }

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
}
