<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\UserBundle\Model\UserManagerInterface;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\UserUpdateProfileType;
use Pumukit\NewAdminBundle\Services\UserStatsService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/user_profile")
 *
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UserProfileController extends AdminController
{
    /** @var TranslatorInterface */
    protected $this->translationService;

    /** @var UserManagerInterface */
    protected $fosUserManager;

    /** @var UserService */
    protected $userService;

    /** @var UserStatsService */
    protected $userStatsService;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        TranslatorInterface $translator,
        UserManagerInterface $fosUserManager,
        UserStatsService $userStatsService
    ) {
        $this->translator = $translator;
        $this->documentManager = $documentManager;
        $this->fosUserManager = $fosUserManager;
        $this->userService = $userService;
        $this->userStatsService = $userStatsService;

        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
    }

    /**
     * @Route("/", name="pumukitnewadmin_profile_user_index")
     * @Template("PumukitNewAdminBundle:UserProfile:template.html.twig")
     */
    public function profileAction(Request $request): array
    {
        $user = $this->getUser();

        $locale = $request->getLocale();
        $form = $this->createForm(UserUpdateProfileType::class, $user, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $user->isLocal()) {
            $this->fosUserManager->updateUser($user);
            $this->userService->update($user);
        }

        return [
            'user' => $user,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:UserProfile:template_user_stats.html.twig")
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
