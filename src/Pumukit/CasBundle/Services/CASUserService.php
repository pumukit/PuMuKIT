<?php

namespace Pumukit\CasBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class CASUserService.
 */
class CASUserService
{
    protected $userService;
    protected $personService;
    protected $casService;
    protected $permissionProfileService;
    protected $groupService;
    protected $dm;

    private $casIdKey;
    private $casCnKey;
    private $casMailKey;
    private $casGivenNameKey;
    private $casSurnameKey;
    private $casGroupKey;
    private $casOriginKey;

    /**
     * CASUserService constructor.
     *
     * @param UserService              $userService
     * @param PersonService            $personService
     * @param CASService               $casService
     * @param PermissionProfileService $permissionProfileService
     * @param GroupService             $groupService
     * @param DocumentManager          $documentManager
     * @param                          $casIdKey
     * @param                          $casCnKey
     * @param                          $casMailKey
     * @param                          $casGivenNameKey
     * @param                          $casSurnameKey
     * @param                          $casGroupKey
     * @param                          $casOriginKey
     */
    public function __construct(UserService $userService, PersonService $personService, CASService $casService, PermissionProfileService $permissionProfileService, GroupService $groupService, DocumentManager $documentManager, $casIdKey, $casCnKey, $casMailKey, $casGivenNameKey, $casSurnameKey, $casGroupKey, $casOriginKey)
    {
        $this->userService = $userService;
        $this->personService = $personService;
        $this->casService = $casService;
        $this->permissionProfileService = $permissionProfileService;
        $this->groupService = $groupService;
        $this->dm = $documentManager;

        $this->casIdKey = $casIdKey;
        $this->casCnKey = $casCnKey;
        $this->casMailKey = $casMailKey;
        $this->casGivenNameKey = $casGivenNameKey;
        $this->casSurnameKey = $casSurnameKey;
        $this->casGroupKey = $casGroupKey;
        $this->casOriginKey = $casOriginKey;
    }

    /**
     * @param $userName
     *
     * @throws \Exception
     *
     * @return User
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

        $user->setOrigin($this->casOriginKey);
        $user->setEnabled(true);

        $this->userService->create($user);

        $this->setCASGroup($attributes, $user);

        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    /**
     * @param User $user
     *
     * @throws \Exception
     */
    public function updateUser(User $user)
    {
        if ($this->casOriginKey === $user->getOrigin()) {
            $attributes = $this->getCASAttributes();

            $casFullName = $this->getCASFullName($attributes);
            $user->setFullname($casFullName);

            $this->setCASGroup($attributes, $user);

            if ((isset($attributes[$this->casMailKey])) && ($attributes[$this->casMailKey] !== $user->getEmail())) {
                $user->setEmail($attributes[$this->casMailKey]);
            }

            $this->dm->persist($user);

            $this->userService->update($user, true, false);
        }
    }

    /**
     * @return mixed
     */
    protected function getCASAttributes()
    {
        $this->casService->forceAuthentication();

        return $this->casService->getAttributes();
    }

    /**
     * @param $userName
     * @param $attributes
     *
     * @return string
     */
    protected function getCASUsername($userName, $attributes)
    {
        return (isset($attributes[$this->casIdKey])) ? $attributes[$this->casIdKey] : $userName;
    }

    /**
     * @param $attributes
     *
     * @return string
     */
    protected function getCASEmail($attributes)
    {
        $mail = (isset($attributes[$this->casMailKey])) ? $attributes[$this->casMailKey] : null;
        if (!$mail) {
            throw new AuthenticationException("Mail can't be null");
        }

        return $mail;
    }

    /**
     * @param $attributes
     *
     * @return string
     */
    protected function getCASFullName($attributes)
    {
        $givenName = (isset($attributes[$this->casGivenNameKey])) ? $attributes[$this->casGivenNameKey] : '';
        $surName = (isset($attributes[$this->casSurnameKey])) ? $attributes[$this->casSurnameKey] : '';

        return $givenName.' '.$surName;
    }

    /**
     * @throws \Exception
     *
     * @return \Pumukit\SchemaBundle\Document\PermissionProfile
     */
    protected function getPermissionProfile()
    {
        $defaultPermissionProfile = $this->permissionProfileService->getDefault();
        if (null === $defaultPermissionProfile) {
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
    protected function setCASGroup($attributes, User $user)
    {
        if (isset($attributes[$this->casGroupKey])) {
            $groupCAS = $this->getGroup($attributes[$this->casGroupKey]);
            foreach ($user->getGroups() as $group) {
                if ($this->casOriginKey === $group->getOrigin()) {
                    $this->userService->deleteGroup($group, $user, true, false);
                }
            }
            $this->userService->addGroup($groupCAS, $user, true, false);
        }
    }

    /**
     * @param $key
     *
     * @throws \Exception
     *
     * @return Group
     */
    protected function getGroup($key)
    {
        $cleanKey = preg_replace('/\W/', '', $key);

        $group = $this->dm->getRepository(Group::class)->findOneBy(
            ['key' => $cleanKey]
        );

        if ($group) {
            return $group;
        }

        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($key);
        $group->setOrigin($this->casOriginKey);
        $this->groupService->create($group);

        return $group;
    }
}
