<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends AdminController
{
    /**
     * Update Action
     * Overwrite to update it with user manager
     * Checks plain password and updates encoded password
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        $config = $this->getConfiguration();

        $userManager = $this->get('fos_user.user_manager');

        $user = $this->findOr404($request);
        $form     = $this->getForm($user);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            // false to not flush
            $userManager->updateUser($user, false);
            // To update aditional fields added
            $this->domainManager->update($user);

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($user, 204));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        return $this->render("PumukitNewAdminBundle:User:update.html.twig",
                             array(
                                   'user' => $user,
                                   'form' => $form->createView()
                                   ));
    }

    /**
     * Check email
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkEmailAction(Request $request)
    {
        $email = $request->get('email', 'default');

        return new JsonResponse(array('usedEmail' =>  $this->checkUsedEmail($email)));
    }

    /**
     * Check used email
     *
     * @param String $email
     * @return boolean TRUE if there is an user with this email, FALSE otherwise
     */
    private function checkUsedEmail($email)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:User');

        $user = $repo->findOneByEmail($email);

        if ($user) return true;

        return false;
    }

    /**
     * Check username
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkUsernameAction(Request $request)
    {
        $username = $request->get('username', 'default');

        return new JsonResponse(array('usedUsername' =>  $this->checkUsedUsername($username)));
    }

    /**
     * Check used username
     *
     * @param String $username
     * @return boolean TRUE if there is an user with this username, FALSE otherwise
     */
    private function checkUsedUsername($username)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:User');

        $user = $repo->findOneByUsername($username);

        if ($user) return true;

        return false;
    }
}