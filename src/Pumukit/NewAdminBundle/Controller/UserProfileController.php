<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Form\Type\UserUpdateProfileType;
use Pumukit\SchemaBundle\Document\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user_profile")
 *
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UserProfileController extends AdminController
{
    /**
     * @Route("/", name="pumukitnewadmin_profile_user_index")
     * @Template("PumukitNewAdminBundle:UserProfile:template.html.twig")
     *
     * @throws \Exception
     */
    public function profileAction(Request $request): array
    {
        $user = $this->getUser();

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $userManager = $this->get('fos_user.user_manager');
        $form = $this->createForm(UserUpdateProfileType::class, $user, ['translator' => $translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $user->isLocal()) {
            $userManager->updateUser($user, false);
            $this->get('pumukitschema.user')->update($user);
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
        $objectsByStatus = $this->get('pumukitnewadmin.user_stats')->getUserMultimediaObjectsGroupByStats($this->getUser());
        $objectsByRole = $this->get('pumukitnewadmin.user_stats')->getUserMultimediaObjectsGroupByRole($this->getUser());
        $userStorage = $this->get('pumukitnewadmin.user_stats')->getUserStorageMB($this->getUser());
        $userDuration = $this->get('pumukitnewadmin.user_stats')->getUserUploadedHours($this->getUser());

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
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $user = $dm->getRepository(User::class)->findOneBy([
            'email' => $updateEmail,
            'username' => ['$ne' => $this->getUser()->getUsername()],
        ]);

        $jsonResponseStatus = -1;
        $message = $this->get('translator')->trans('Email already in use');
        if (!$user || $this->getUser()->getEmail() === $updateEmail) {
            $jsonResponseStatus = 0;
            $message = $this->get('translator')->trans('User info updated');
        }

        return new JsonResponse(['status' => $jsonResponseStatus, 'message' => $message]);
    }
}
