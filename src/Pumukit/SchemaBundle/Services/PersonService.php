<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\UserService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Collections\ArrayCollection;

class PersonService
{
    private $dm;
    private $dispatcher;
    private $repoPerson;
    private $repoMmobj;
    private $userService;
    private $addUserAsPerson;
    private $personalScopeRoleCode;
    private $repoRole;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     * @param PersonWithRoleEventDispatcherService $dispatcher
     * @param UserService     $userService
     * @param boolean         $addUserAsPerson
     * @param string          $personalScopeRoleCode
     */
    public function __construct(DocumentManager $documentManager, PersonWithRoleEventDispatcherService $dispatcher, UserService $userService, $addUserAsPerson=true, $personalScopeRoleCode='owner')
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->userService = $userService;
        $this->repoPerson = $documentManager->getRepository('PumukitSchemaBundle:Person');
        $this->repoMmobj = $documentManager->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->repoRole = $documentManager->getRepository('PumukitSchemaBundle:Role');
        $this->addUserAsPerson = $addUserAsPerson;
        $this->personalScopeRoleCode = $personalScopeRoleCode;
    }

    /**
     * Save Person
     *
     * @param  Person $person
     * @return Person
     */
    public function savePerson(Person $person)
    {
        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    /**
     * Save Role
     *
     * @param  Role $role
     * @return Role
     */
    public function saveRole(Role $role)
    {
        $this->dm->persist($role);
        $this->dm->flush();

        return $role;
    }

    /**
     * Find person by id
     *
     * @param  string $id
     * @return Person
     */
    public function findPersonById($id)
    {
        return $this->repoPerson->find($id);
    }

    /**
     * Find role by id
     *
     * @param  string $id
     * @return Role
     */
    public function findRoleById($id)
    {
        return $this->repoRole->find($id);
    }

    /**
     * Find person by email
     *
     * @param  string $email
     * @return Person
     */
    public function findPersonByEmail($email)
    {
        return $this->repoPerson->findOneByEmail($email);
    }

    /**
     * Update update person
     *
     * @param  Person $person
     * @return Person
     */
    public function updatePerson(Person $person)
    {
        $person = $this->savePerson($person);

        foreach ($this->repoMmobj->findByPersonId($person->getId()) as $mmobj) {
            $embeddedRoles = $mmobj->getAllEmbeddedRolesByPerson($person);
            foreach ($mmobj->getAllEmbeddedPeopleByPerson($person) as $embeddedPerson) {
                $embeddedPerson = $this->updateEmbeddedPerson($person, $embeddedPerson);
                foreach ($embeddedRoles as $embeddedRole) {
                    $this->dispatcher->dispatchUpdate($mmobj, $embeddedPerson, $embeddedRole);
                }
            }
            $this->dm->persist($mmobj);
        }
        $this->dm->flush();

        return $person;
    }

    /**
     * Update update role
     *
     * @param  Role $role
     * @return Role
     */
    public function updateRole(Role $role)
    {
        $role = $this->saveRole($role);

        foreach ($this->repoMmobj->findByRoleId($role->getId()) as $mmobj) {
            foreach ($mmobj->getRoles() as $embeddedRole) {
                if ($role->getId() === $embeddedRole->getId()) {
                    $embeddedRole = $this->updateEmbeddedRole($role, $embeddedRole);
                    $this->dm->persist($mmobj);
                    foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                        $this->dispatcher->dispatchUpdate($mmobj, $embeddedPerson, $embeddedRole);
                    }
                }
            }
        }
        $this->dm->flush();

        return $role;
    }

    /**
     * Find series with person
     *
     * @param  Person $person
     * @param  int    $limit  Number of series, all by default
     * @return array
     */
    public function findSeriesWithPerson(Person $person, $limit = 0)
    {
        $mmobjs = $this->repoMmobj->findByPersonId($person->getId());

        $seriesCollection = new ArrayCollection();
        $count = 0;
        foreach ($mmobjs as $mmobj) {
            if ($limit !== 0) {
                if ($count === $limit) {
                    break;
                }
            }
            $oneseries = $mmobj->getSeries();
            if (!$seriesCollection->contains($oneseries)) {
                $seriesCollection->add($oneseries);
            }
            ++$count;
        }

        return $seriesCollection;
    }

    /**
     * Create relation person
     *
     * @param  Person           $person
     * @param  Role             $role
     * @param  MultimediaObject $multimediaObject
     * @return MultimediaObject
     */
    public function createRelationPerson(Person $person, Role $role, MultimediaObject $multimediaObject, $flush = true)
    {
        $this->dm->persist($person);
        $multimediaObject->addPersonWithRole($person, $role);
        $role->increaseNumberPeopleInMultimediaObject();
        if ($this->addUserAsPerson && ($this->personalScopeRoleCode === $role->getCod()) && (null != $person->getUser())) {
            $this->userService->addOwnerUserToMultimediaObject($multimediaObject, $person->getUser(), false);
        }
        $this->dm->persist($multimediaObject);
        $this->dm->persist($role);

        if ($flush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchCreate($multimediaObject, $person, $role);

        return $multimediaObject;
    }

    /**
     * Auto complete
     *
     * Returns people with partial name in it
     *
     * @param  string          $name
     * @return ArrayCollection
     */
    public function autoCompletePeopleByName($name)
    {
        return $this->repoPerson->findByName(new \MongoRegex('/'.$name.'/i'));
    }

    /**
     * Up person with role
     *
     * @param  Person           $person
     * @param  Role             $role
     * @param  MultimediaObject $multimediaObject
     * @return MultimediaObject
     */
    public function upPersonWithRole(Person $person, Role $role, MultimediaObject $multimediaObject)
    {
        $multimediaObject->upPersonWithRole($person, $role);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Down person with role
     *
     * @param  Person           $person
     * @param  Role             $role
     * @param  MultimediaObject $multimediaObject
     * @return MultimediaObject
     */
    public function downPersonWithRole(Person $person, Role $role, MultimediaObject $multimediaObject)
    {
        $multimediaObject->downPersonWithRole($person, $role);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Delete relation of embedded person with role in multimedia object
     *
     * @param  Person           $person
     * @param  Role             $role
     * @param  MultimediaObject $multimediaObject
     * @return boolean          TRUE if this multimedia_object contained the specified person_in_multimedia_object, FALSE otherwise.
     */
    public function deleteRelation(Person $person, Role $role, MultimediaObject $multimediaObject)
    {
        $hasBeenRemoved = $multimediaObject->removePersonWithRole($person, $role);
        if ($hasBeenRemoved) {
            $role->decreaseNumberPeopleInMultimediaObject();
            if ($this->addUserAsPerson && ($this->personalScopeRoleCode === $role->getCod()) && (null != $person->getUser())) {
                $this->userService->removeOwnerUserFromMultimediaObject($multimediaObject, $person->getUser(), false);
            }
        }
        $this->dm->persist($multimediaObject);
        $this->dm->persist($role);
        $this->dm->flush();

        $this->dispatcher->dispatchDelete($multimediaObject, $person, $role);

        return $multimediaObject;
    }

    /**
     * Delete Person
     */
    public function deletePerson(Person $person, $deleteFromUser=false)
    {
        if (0 !== count($this->repoMmobj->findByPersonId($person->getId()))) {
            throw new \Exception("Couldn't remove Person with id ".$person->getId().". There are multimedia objects with this person");
        }

        if ((null != $user = $person->getUser()) && !$deleteFromUser) {
            throw new \Exception('Could not remove Person with id "'.$person->getId().'". There is an User with id "'.$user->getId().'" and usernname "'.$user->getUsername().'" referenced. Delete the user to delete this Person.');
        }

        $this->dm->remove($person);
        $this->dm->flush();
    }

    /**
     * Batch delete person
     *
     * @param Person
     */
    public function batchDeletePerson(Person $person)
    {
        foreach ($this->repoMmobj->findByPersonId($person->getId()) as $mmobj) {
            foreach ($mmobj->getRoles() as $embeddedRole) {
                if ($mmobj->containsPersonWithRole($person, $embeddedRole)) {
                    if (!($mmobj->removePersonWithRole($person, $embeddedRole))) {
                        throw new \Expection('There was an error removing person '.$person->getId().' with role '.$role->getCod().' in multimedia object '.$multimediaObject->getId());
                    }
                    $this->dispatcher->dispatchDelete($mmobj, $person, $embeddedRole);
                }
            }
            $this->dm->persist($mmobj);
        }

        $this->dm->remove($person);
        $this->dm->flush();
    }

    /**
     * Count multimedia objects with person
     *
     * @param  Person $person
     * @return array
     */
    public function countMultimediaObjectsWithPerson($person)
    {
        return count($this->repoMmobj->findByPersonId($person->getId()));
    }

    /**
     * Reference Person into User
     *
     * @param User $user
     * @return User
     */
    public function referencePersonIntoUser(User $user)
    {
        if ($this->addUserAsPerson && (null == $person = $user->getPerson())) {
            $person = $this->createFromUser($user);

            $user->setPerson($person);
            $person->setUser($user);

            $this->dm->persist($user);
            $this->dm->persist($person);
            $this->dm->flush();
        }

        return $user;
    }

    /**
     * Get Person from logged in User
     *
     * Get the Person referenced
     * in the logged in User
     * It there is none, it creates it
     *
     * @param  User|null  $loggedInUser
     * @return Person|null
     */
    public function getPersonFromLoggedInUser(User $loggedInUser = null)
    {
        if (null != $loggedInUser) {
            if (null == $person = $loggedInUser->getPerson()) {
                $loggedInUser = $this->referencePersonIntoUser($loggedInUser);
                $person = $loggedInUser->getPerson();
            }

            return $person;
        }

        return null;
    }

    /**
     * Get Personal Scope Role
     *
     * Gets the default role
     * to add the User as Person
     * to MultimediaObject
     *
     * @return Role|null
     */
    public function getPersonalScopeRole()
    {
        $personalScopeRole = $this->dm->getRepository('PumukitSchemaBundle:Role')->findOneByCod($this->personalScopeRoleCode);
        if ($this->addUserAsPerson && (null == $personalScopeRole)) {
            throw new \Exception('Invalid Personal Scope Role Code: "'.$this->personalScopeRoleCode
                                 .'". There is no Role with this data. '
                                 .'Change it on parameters.yml or use default value by deleting '
                                 .'line "personal_scope_role_code: \''.$this->personalScopeRoleCode.'\'" '
                                 .'from your parameters file.');
        }

        return $personalScopeRole;
    }

    /**
     * Get Personal Scope Role
     *
     * Gets the default role code
     * to add the User as Person
     * to MultimediaObject
     *
     * @return Role
     */
    public function getPersonalScopeRoleCode()
    {
        return $this->personalScopeRoleCode;
    }

    /**
     * Create from User
     *
     * @param User $user
     * @return Person
     */
    private function createFromUser(User $user)
    {
        $person = new Person();

        $person->setName($user->getFullname() ?: $user->getUsername());
        $person->setEmail($user->getEmail());

        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    /**
     * Get all roles
     */
    public function getRoles()
    {
        $criteria = array();
        $sort = array('rank' => 1);
        return $this->repoRole->findBy($criteria, $sort);
    }

    /**
     * Remove User from Person
     *
     * @param User    $user
     * @param Person  $person
     * @param boolean $executeFlush
     */
    public function removeUserFromPerson(User $user, Person $person, $executeFlush = true)
    {
        $person->setUser(null);
        $this->dm->persist($person);
        if ($executeFlush) {
            $this->dm->flush();
        }
    }

    /**
     * Update embedded person
     *
     * @param  Person         $person
     * @param  EmbeddedPerson $embeddedPerson
     * @return EmbeddedPerson
     */
    private function updateEmbeddedPerson(Person $person, EmbeddedPerson $embeddedPerson)
    {
        if (null !== $person) {
            $embeddedPerson->setName($person->getName());
            $embeddedPerson->setEmail($person->getEmail());
            $embeddedPerson->setWeb($person->getWeb());
            $embeddedPerson->setPhone($person->getPhone());
            $embeddedPerson->setI18nHonorific($person->getI18nHonorific());
            $embeddedPerson->setI18nFirm($person->getI18nFirm());
            $embeddedPerson->setI18nPost($person->getI18nPost());
            $embeddedPerson->setI18nBio($person->getI18nBio());
        }

        return $embeddedPerson;
    }

    /**
     * Update embedded role
     *
     * @param  Role         $role
     * @param  EmbeddedRole $embeddedRole
     * @return EmbeddedRole
     */
    private function updateEmbeddedRole(Role $role, EmbeddedRole $embeddedRole)
    {
        if (null !== $role) {
            $embeddedRole->setCod($role->getCod());
            $embeddedRole->setXml($role->getXml());
            $embeddedRole->setDisplay($role->getDisplay());
            $embeddedRole->setI18nName($role->getI18nName());
            $embeddedRole->setLocale($role->getLocale());
        }

        return $embeddedRole;
    }
}
