<?php

namespace Pumukit\LDAPBundle\EventListener;

use Pumukit\LDAPBundle\Services\LDAPService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface
{
    const LDAP_ID_KEY = 'uid';

    protected $dm;
    protected $userService;
    protected $LDAPService;
    protected $permissionProfile;

    public function __construct(
        ContainerInterface $container,
        Router $router,
        DocumentManager $dm,
        UserService $userService,
        PersonService $personService,
        LDAPService $LDAPService,
        PermissionProfileService $permissionProfileService,
        Session $session,
        HttpUtils $httpUtils
    ) {
        $this->router = $router;
        $this->container = $container;
        $this->dm = $dm;
        $this->userService = $userService;
        $this->ldapService = $LDAPService;
        $this->permissionProfileService = $permissionProfileService;
        $this->session = $session;
        $this->httpUtils = $httpUtils;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $info = $this->ldapService->getInfoFrom(self::LDAP_ID_KEY, $token->getUser()->getUsername());
        if (!isset($info) || !$info) {
            throw new \RuntimeException('User not found.');
        }

        $pumukitUser = $this->dm->getRepository('PumukitSchemaBundle:User')->findOneBy(
            array('username' => $token->getUser()->getUsername())
        );

        if (count($pumukitUser) <= 0) {
            try {
                $pumukitUser = $this->createUser($info, $token);
            } catch (\Exception $e) {
                throw  $e;
            }
        }
        $this->promoteUser($info, $pumukitUser);

        $token = new UsernamePasswordToken($pumukitUser, null, 'user', $pumukitUser->getRoles());
        $this->container->get('security.context')->setToken($token);

        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl());
    }

    private function createUser($info, $token)
    {
        $user = new User();
        $user->setEmail($info['mail'][0]);
        $user->setUsername($token->getUser()->getUsername());
        $user->setFullname($info['cn'][0]);

        $permissionProfile = $this->permissionProfileService->getByName('Viewer');
        $user->setPermissionProfile($permissionProfile);
        $user->setOrigin('ldap');
        $user->setEnabled(true);

        $this->userService->create($user);

        if(isset($info['edupersonaffiliation'][0])) {
            foreach ($info['edupersonaffiliation'][0] as $key) {
                $group = $this->getGroup($key);
                $this->userService->addGroup($group, $user, true, false);
                $this->personService->referencePersonIntoUser($user);
            }
        }

        return $user;
    }

    private function getGroup($key)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:Group');
        $groupService = $this->get('pumukitschema.group');


        $cleanKey = preg_replace('/\W/', '', $key);
        $group = $repo->findOneByKey($cleanKey);
        if ($group) {
            return $group;
        }
        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($key);
        $group->setOrigin('ldap');
        $groupService->create($group);
        return $group;
    }

    private function promoteUser($info, $user)
    {
        $permissionProfileAutoPub = $this->permissionProfileService->getByName('Auto Publisher');
        $permissionProfileAdmin = $this->permissionProfileService->getByName('Administrator');

        if ($this->isAutoPub($info)) {
            $user->setPermissionProfile($permissionProfileAutoPub);
            $this->userService->update($user, true, false);
        }

        if ($this->isAdmin($info)) {
            $user->setPermissionProfile($permissionProfileAdmin);
            $this->userService->update($user, true, false);
        }
    }

    private function determineTargetUrl()
    {
        if (null !== $this->session->get('_security.main.target_path')) {
            return $this->session->get('_security.main.target_path');
        } else {
            return $this->session->get('target_path');
        }
    }

    private function isAutoPub($info)
    {
        return in_array('urn:mace:rediris.es:ehu.es:entitlement:service:pumukit', $info['irisuserentitlement']);
    }

    private function isAdmin($info)
    {
        return false;
    }
}
