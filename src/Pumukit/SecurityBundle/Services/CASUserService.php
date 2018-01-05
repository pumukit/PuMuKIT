<?php

namespace Pumukit\SecurityBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;

class CASUserService
{
    const CAS_ID_KEY = 'UID';

    const CAS_CN_KEY = 'CN';
    const CAS_MAIL_KEY = 'MAIL';
    const CAS_GIVENNAME_KEY = 'GIVENNAME';
    const CAS_SURNAME_KEY = 'SURNAME';
    const CAS_GROUP_KEY = 'GROUP';

    const ORIGIN = 'cas';

    private $userService;
    private $personService;
    private $casService;
    private $permissionProfileService;
    private $groupService;
    private $dm;

    public function __construct(UserService $userService, PersonService $personService, CASService $casService, PermissionProfileService $permissionProfileService, GroupService $groupService, DocumentManager $documentManager)
    {
        $this->userService = $userService;
        $this->personService = $personService;
        $this->casService = $casService;
        $this->permissionProfileService = $permissionProfileService;
        $this->groupService = $groupService;
        $this->dm = $documentManager;
    }

    /**
     * @param $userName
     *
     * @return User
     *
     * @throws \Exception
     */
    public function createDefaultUser($userName)
    {
        $attributes = $this->getCASAttributes();

        $user = new User();

        $casUserName = $this->getCASUsername($userName, $attributes);
        $user->setUsername($casUserName);

        $casEmail = $this->getCASEmail($attributes);
        if ($casEmail) {
            $user->setEmail($casEmail);
        }

        $casFullName = $this->getCASFullName($attributes);
        $user->setFullname($casFullName);

        $defaultPermissionProfile = $this->getPermissionProfile();
        $user->setPermissionProfile($defaultPermissionProfile);

        $user->setOrigin(self::ORIGIN);
        $user->setEnabled(true);

        $this->userService->create($user);

        $this->setCASGroup($attributes, $user);

        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    public function updateUser(User $user)
    {
        $attributes = $this->getCASAttributes();

        if ((isset($attributes[self::CAS_MAIL_KEY])) && ($attributes[self::CAS_MAIL_KEY] !== $user->getEmail())) {
            $user->setEmail($attributes[self::CAS_MAIL_KEY]);
            $this->dm->persist($user);
            $this->dm->flush();
        }
    }

    /**
     * @return mixed
     */
    private function getCASAttributes()
    {
        $this->casService->forceAuthentication();
        $attributes = $this->casService->getAttributes();

        return $attributes;
    }

    /**
     * @param $userName
     * @param $attributes
     *
     * @return string
     */
    protected function getCASUsername($userName, $attributes)
    {
        return (isset($attributes[self::CAS_ID_KEY])) ? $attributes[self::CAS_ID_KEY] : $userName;
    }

    /**
     * @param $attributes
     *
     * @return null|string
     */
    protected function getCASEmail($attributes)
    {
        return (isset($attributes[self::CAS_MAIL_KEY])) ?: null;
    }

    /**
     * @param $attributes
     *
     * @return string
     */
    protected function getCASFullName($attributes)
    {
        $givenName = (isset($attributes[self::CAS_GIVENNAME_KEY])) ?: '';
        $surName = (isset($attributes[self::CAS_SURNAME_KEY])) ?: '';

        return $givenName.$surName;
    }

    /**
     * @return \Pumukit\SchemaBundle\Document\PermissionProfile
     *
     * @throws \Exception
     */
    protected function getPermissionProfile()
    {
        $defaultPermissionProfile = $this->permissionProfileService->getDefault();
        if (null == $defaultPermissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
        }

        return $defaultPermissionProfile;
    }

    /**
     * @param $attributes
     * @param $user
     *
     * @throws \Exception
     */
    protected function setCASGroup($attributes, $user)
    {
        if (isset($attributes[self::CAS_GROUP_KEY])) {
            $group = $this->getGroup($attributes[self::CAS_GROUP_KEY]);
            $this->userService->addGroup($group, $user, true, false);
        }
    }

    /**
     * @param $key
     *
     * @return Group
     *
     * @throws \Exception
     */
    protected function getGroup($key)
    {
        $cleanKey = preg_replace('/\W/', '', $key);

        $group = $this->dm->getRepository('PumukitSchemaBundle:Group')->findOneByKey($cleanKey);
        if ($group) {
            return $group;
        }

        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($key);
        $group->setOrigin(self::ORIGIN);
        $this->groupService->create($group);

        return $group;
    }
}
