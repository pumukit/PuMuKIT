<?php

namespace Pumukit\LDAPBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LDAPUserService
{
    const EDU_PERSON_AFFILIATION = 'edupersonaffiliation';
    const IRISCLASSIFCODE = 'irisclassifcode';
    const ORIGIN = 'ldap';

    protected $dm;
    protected $userService;
    protected $ldapService;
    protected $permissionProfile;
    protected $logger;
    protected $personService;
    protected $permissionProfileService;
    protected $groupService;

    /**
     * LDAPUserService constructor.
     *
     * @param DocumentManager          $documentManager
     * @param UserService              $userService
     * @param PersonService            $personService
     * @param LDAPService              $LDAPService
     * @param PermissionProfileService $permissionProfile
     * @param GroupService             $groupService
     * @param LoggerInterface          $logger
     */
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

    /**
     * @param $info
     * @param $username
     *
     * @throws \Exception
     *
     * @return mixed|object|User
     */
    public function createUser($info, $username)
    {
        if (!isset($username)) {
            throw new \InvalidArgumentException('Uid is not set ');
        }

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => $username]);
        if (count($user) <= 0) {
            try {
                $user = $this->newUser($info, $username);
            } catch (\Exception $e) {
                throw new AuthenticationException($e->getMessage());
            }
        } elseif ($info['mail'][0] !== $user->getEmail() || $info['cn'][0] !== $user->getFullname()) {
            try {
                $user = $this->updateUser($info, $user);
            } catch (\Exception $e) {
                throw new AuthenticationException($e->getMessage());
            }
        }
        $this->updateGroups($info, $user);
        $this->promoteUser($info, $user);

        return $user;
    }

    /**
     * @param $info
     *
     * @return mixed
     */
    public function getEmail($info)
    {
        if (isset($info['mail'][0])) {
            return $info['mail'][0];
        }

        throw new AuthenticationException('Missing LDAP attribute email');
    }

    /**
     * @param $info
     * @param $username
     *
     * @return object|User
     */
    protected function newUser($info, $username)
    {
        $email = $this->getEmail($info);

        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);
        if (count($user) <= 0) {
            $user = new User();
            $user->setEmail($email);
        } else {
            throw new AuthenticationException('Duplicated email key');
        }

        if (isset($info['mail'][0])) {
            $user->setEmail($info['mail'][0]);
        }

        $user->setUsername($username);

        if (isset($info['cn'][0])) {
            $user->setFullname($info['cn'][0]);
        }

        $permissionProfile = $this->permissionProfileService->getByName('Viewer');
        $user->setPermissionProfile($permissionProfile);
        $user->setOrigin(self::ORIGIN);
        $user->setEnabled(true);

        $this->userService->create($user);
        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @throws \Exception
     *
     * @return Group
     */
    protected function getGroup($key, $type = null)
    {
        $cleanKey = $this->getGroupKey($key, $type);
        $cleanName = $this->getGroupName($key, $type);

        $group = $this->dm->getRepository(Group::class)->findOneByKey($cleanKey);
        if ($group) {
            return $group;
        }
        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($cleanName);
        $group->setOrigin(self::ORIGIN);
        $this->groupService->create($group);

        return $group;
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return null|string|string[]
     */
    protected function getGroupKey($key, $type = null)
    {
        return preg_replace('/\W/', '', $key);
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return mixed
     */
    protected function getGroupName($key, $type = null)
    {
        return $key;
    }

    /**
     * @param $info
     * @param $user
     *
     * @throws \Exception
     */
    protected function promoteUser($info, $user)
    {
        $permissionProfileAutoPub = $this->permissionProfileService->getByName('Auto Publisher');
        $permissionProfileAdmin = $this->permissionProfileService->getByName('Administrator');
        $permissionProfileIngestor = $this->permissionProfileService->getByName('Ingestor');
        $permissionProfilePublisher = $this->permissionProfileService->getByName('Publisher');
        $permissionProfileViewer = $this->permissionProfileService->getByName('Viewer');

        if ($this->isAutoPub($info, $user->getUsername())) {
            $user->setPermissionProfile($permissionProfileAutoPub);
            $this->userService->update($user, true, false);
        }

        if ($this->isAdmin($info, $user->getUsername())) {
            $user->setPermissionProfile($permissionProfileAdmin);
            $this->userService->update($user, true, false);
        }

        if ($this->isIngestor($info, $user->getUsername())) {
            $user->setPermissionProfile($permissionProfileIngestor);
            $this->userService->update($user, true, false);
        }

        if ($this->isPublisher($info, $user->getUsername())) {
            $user->setPermissionProfile($permissionProfilePublisher);
            $this->userService->update($user, true, false);
        }

        if ($this->isViewer($info, $user->getUsername())) {
            $user->setPermissionProfile($permissionProfileViewer);
            $this->userService->update($user, true, false);
        }
    }

    /**
     * @param $info
     * @param $user
     *
     * @return mixed
     */
    protected function updateGroups($info, $user)
    {
        $aGroups = [];
        if (isset($info[self::EDU_PERSON_AFFILIATION][0])) {
            foreach ($info[self::EDU_PERSON_AFFILIATION] as $key => $value) {
                if ('count' !== $key) {
                    try {
                        $group = $this->getGroup($value, self::EDU_PERSON_AFFILIATION);
                        $this->userService->addGroup($group, $user, true, false);
                        $aGroups[] = $group->getKey();
                        $this->logger->info(__CLASS__.' ['.__FUNCTION__.'] '.'Added Group: '.$group->getName());
                    } catch (\ErrorException $e) {
                        $this->logger->info(
                            __CLASS__.' ['.__FUNCTION__.'] '.'Invalid Group '.$value.': '.$e->getMessage()
                        );
                    } catch (\Exception $e) {
                        $this->logger->error(
                            __CLASS__.' ['.__FUNCTION__.'] '.'Error on adding Group '.$value.': '.$e->getMessage()
                        );
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
                        $aGroups[] = $group->getKey();
                        $this->logger->info(__CLASS__.' ['.__FUNCTION__.'] '.'Added Group: '.$group->getName());
                    } catch (\ErrorException $e) {
                        $this->logger->info(
                            __CLASS__.' ['.__FUNCTION__.'] '.'Invalid Group '.$value.': '.$e->getMessage()
                        );
                    } catch (\Exception $e) {
                        $this->logger->error(
                            __CLASS__.' ['.__FUNCTION__.'] '.'Error on adding Group '.$value.': '.$e->getMessage()
                        );
                    }
                }
            }
        }

        foreach ($user->getGroups() as $group) {
            if (self::ORIGIN === $group->getOrigin()) {
                if (!in_array($group->getKey(), $aGroups)) {
                    try {
                        $this->userService->deleteGroup($group, $user, true, false);
                    } catch (\Exception $e) {
                        $this->logger->error(__CLASS__.' ['.__FUNCTION__.'] '.'Delete group '.$group->getKey().' from user  : '.$e->getMessage());
                    }
                }
            }
        }

        return $user;
    }

    /**
     * @param $info
     * @param $user
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function updateUser($info, $user)
    {
        if (isset($info['mail'][0])) {
            $user->setEmail($info['mail'][0]);
        }

        if (isset($info['cn'][0])) {
            $user->setFullname($info['cn'][0]);
        }

        $this->userService->update($user, true, false);

        return $user;
    }

    /**
     * @param $info
     * @param $username
     *
     * @return bool
     */
    protected function isAutoPub($info, $username)
    {
        return false;
    }

    /**
     * @param $info
     * @param $username
     *
     * @return bool
     */
    protected function isAdmin($info, $username)
    {
        return false;
    }

    /**
     * @param $info
     * @param $username
     *
     * @return bool
     */
    protected function isIngestor($info, $username)
    {
        return false;
    }

    /**
     * @param $info
     * @param $username
     *
     * @return bool
     */
    protected function isPublisher($info, $username)
    {
        return false;
    }

    /**
     * @param $info
     * @param $username
     *
     * @return bool
     */
    protected function isViewer($info, $username)
    {
        return false;
    }
}
