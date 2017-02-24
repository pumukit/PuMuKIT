<?php

namespace Pumukit\LDAPBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class LDAPUserService
{
    const EDU_PERSON_AFFILIATION = 'edupersonaffiliation';
    const IRISCLASSIFCODE = 'irisclassifcode';

    protected $dm;
    protected $userService;
    protected $ldapService;
    protected $permissionProfile;
    protected $logger;

    public function __construct(DocumentManager $documentManager, UserService $userService, PersonService $personService, LDAPService $LDAPService, PermissionProfileService $permissionProfile, GroupService $groupService, LoggerInterface $logger)
    {
        $this->dm = $documentManager;
        $this->userService = $userService;
        $this->personService = $personService;
        $this->ldapService = $LDAPService;
        $this->permissionProfileService = $permissionProfile;
        $this->groupService = $groupService;
        $this->logger = $logger;
    }

    public function createUser($info, $username)
    {
        if (!isset($username)) {
            throw new \InvalidArgumentException('Uid is not set ');
        }

        $user = $this->dm->getRepository('PumukitSchemaBundle:User')->findOneBy(array('username' => $username));
        if (count($user) <= 0) {
            try {
                $user = $this->newUser($info, $username);
            } catch (\Exception $e) {
                throw  $e;
            }
        }
        $this->promoteUser($info, $user);

        return $user;
    }

    private function newUser($info, $username)
    {
        $user = new User();

        if (isset($info['mail'][0])) {
            $user->setEmail($info['mail'][0]);
        }

        $user->setUsername($username);

        if (isset($info['cn'][0])) {
            $user->setFullname($info['cn'][0]);
        }

        $permissionProfile = $this->permissionProfileService->getByName('Viewer');
        $user->setPermissionProfile($permissionProfile);
        $user->setOrigin('ldap');
        $user->setEnabled(true);

        $this->userService->create($user);
        $this->personService->referencePersonIntoUser($user);

        if (isset($info[self::EDU_PERSON_AFFILIATION][0])) {
            foreach ($info[self::EDU_PERSON_AFFILIATION] as $key => $value) {
                if ('count' !== $key) {
                    try {
                        $group = $this->getGroup($value, self::EDU_PERSON_AFFILIATION);
                        $this->userService->addGroup($group, $user, true, false);
                        $this->logger->info(__CLASS__.' ['.__FUNCTION__.'] '.'Added Group: '.$group->getName());
                    } catch (\Exception $e) {
                        $this->logger->error(__CLASS__.' ['.__FUNCTION__.'] '.'Error on adding Group '.$value.': '.$e->getMessage());
                    }
                }
            }
        }

        if (isset($info[self::IRISCLASSIFCODE][0])) {
            foreach ($info[self::IRISCLASSIFCODE] as $key => $value) {
                if ('count' !== $key) {
                    try {
                        $group = $this->getGroup($value, self::IRISCLASSIFCODE);
                        $this->userService->addGroup($group, $user, true, false);
                        $this->logger->info(__CLASS__.' ['.__FUNCTION__.'] '.'Added Group: '.$group->getName());
                    } catch (\Exception $e) {
                        $this->logger->error(__CLASS__.' ['.__FUNCTION__.'] '.'Error on adding Group '.$value.': '.$e->getMessage());
                    }
                }
            }
        }

        return $user;
    }

    protected function getGroup($key, $type = null)
    {
        $cleanKey = preg_replace('/\W/', '', $key);
        $cleanName = $this->getGroupName($key, $type);

        $group = $this->dm->getRepository('PumukitSchemaBundle:Group')->findOneByKey($cleanKey);
        if ($group) {
            return $group;
        }
        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($cleanName);
        $group->setOrigin('ldap');
        $this->groupService->create($group);

        return $group;
    }

    protected function getGroupName($key, $type = null)
    {
        return $key;
    }

    private function promoteUser($info, $user)
    {
        $permissionProfileAutoPub = $this->permissionProfileService->getByName('Auto Publisher');
        $permissionProfileAdmin = $this->permissionProfileService->getByName('Administrator');

        if ($this->isAutoPub($info, $user->getUsername())) {
            $user->setPermissionProfile($permissionProfileAutoPub);
            $this->userService->update($user, true, false);
        }

        if ($this->isAdmin($info, $user->getUsername())) {
            $user->setPermissionProfile($permissionProfileAdmin);
            $this->userService->update($user, true, false);
        }
    }

    protected function isAutoPub($info, $username)
    {
        return false;
    }

    protected function isAdmin($info, $username)
    {
        return false;
    }
}
