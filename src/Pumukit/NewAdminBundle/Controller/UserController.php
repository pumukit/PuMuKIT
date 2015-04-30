<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
        $form = $this->getForm($user);

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
     * Delete action
     */
    public function deleteAction(Request $request)
    {
        $repo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:User');

        if( 1 == $repo->createQueryBuilder()->getQuery()->execute()->count()){
          return new Response('Can not delete this unique user', 409);
        }

        return parent::deleteAction($request);
    }

}