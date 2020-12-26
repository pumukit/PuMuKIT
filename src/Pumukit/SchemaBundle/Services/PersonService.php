<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\PersonInterface;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\RoleInterface;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Repository\MultimediaObjectRepository;
use Pumukit\SchemaBundle\Repository\PersonRepository;
use Pumukit\SchemaBundle\Repository\RoleRepository;
use Pumukit\SchemaBundle\Utils\Search\SearchUtils;

class PersonService
{
    /** @var DocumentManager */
    private $dm;
    /** @var PersonWithRoleEventDispatcherService */
    private $dispatcher;
    /** @var PersonRepository */
    private $repoPerson;
    /** @var MultimediaObjectRepository */
    private $repoMmobj;
    /** @var UserService */
    private $userService;
    private $addUserAsPerson;
    private $personalScopeRoleCode;
    /** @var RoleRepository */
    private $repoRole;

    public function __construct(DocumentManager $documentManager, PersonWithRoleEventDispatcherService $dispatcher, UserService $userService, $addUserAsPerson = true, $personalScopeRoleCode = 'owner')
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->userService = $userService;
        $this->repoPerson = $documentManager->getRepository(Person::class);
        $this->repoMmobj = $documentManager->getRepository(MultimediaObject::class);
        $this->repoRole = $documentManager->getRepository(Role::class);
        $this->addUserAsPerson = $addUserAsPerson;
        $this->personalScopeRoleCode = $personalScopeRoleCode;
    }

    public function savePerson(PersonInterface $person): PersonInterface
    {
        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    public function saveRole(RoleInterface $role): RoleInterface
    {
        $this->dm->persist($role);
        $this->dm->flush();

        return $role;
    }

    public function findPersonById(string $id)
    {
        return $this->repoPerson->find($id);
    }

    public function findRoleById(string $id)
    {
        return $this->repoRole->find($id);
    }

    public function findPersonByEmail(string $email)
    {
        return $this->repoPerson->findOneBy(['email' => $email]);
    }

    public function updatePerson(PersonInterface $person): PersonInterface
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

    public function updateRole(RoleInterface $role): RoleInterface
    {
        $role = $this->saveRole($role);

        $qb = $this->dm->createQueryBuilder(MultimediaObject::class);

        $query = $qb
            ->updateMany()
            ->field('people._id')->equals(new ObjectId($role->getId()))
            ->field('people.$.cod')->set($role->getCod())
            ->field('people.$.xml')->set($role->getXml())
            ->field('people.$.display')->set($role->getDisplay())
            ->field('people.$.name')->set($role->getI18nName())
            ->field('people.$.text')->set($role->getI18nText())
            ->getQuery()
        ;
        $query->execute();

        $this->dm->flush();

        return $role;
    }

    public function findSeriesWithPerson(PersonInterface $person, int $limit = 0): ArrayCollection
    {
        $mmobjs = $this->repoMmobj->findByPersonId($person->getId());

        $seriesCollection = new ArrayCollection();
        $count = 0;
        foreach ($mmobjs as $mmobj) {
            if (0 !== $limit) {
                if ($count === $limit) {
                    break;
                }
            }
            $oneseries = $mmobj->getSeries();
            if (!$seriesCollection->contains($oneseries)) {
                $seriesCollection->add($oneseries);
                ++$count;
            }
        }

        return $seriesCollection;
    }

    public function createRelationPerson(PersonInterface $person, RoleInterface $role, MultimediaObject $multimediaObject, bool $flush = true, bool $dispatch = true): MultimediaObject
    {
        $this->dm->persist($person);
        $multimediaObject->addPersonWithRole($person, $role);
        $role->increaseNumberPeopleInMultimediaObject();
        if ($this->addUserAsPerson && ($this->personalScopeRoleCode === $role->getCod()) && (null !== $person->getUser())) {
            $this->userService->addOwnerUserToMultimediaObject($multimediaObject, $person->getUser(), false);
        }
        $this->dm->persist($multimediaObject);
        $this->dm->persist($role);

        if ($flush) {
            $this->dm->flush();
        }

        if ($dispatch) {
            $this->dispatcher->dispatchCreate($multimediaObject, $person, $role);
        }

        return $multimediaObject;
    }

    public function autoCompletePeopleByName(string $name, array $exclude = [], bool $checkAccents = false)
    {
        if ($checkAccents) {
            //Wating for Mongo 4 and https://docs.mongodb.com/manual/reference/collation/
            $name = SearchUtils::scapeTildes($name);
        }

        $qb = $this->repoPerson->createQueryBuilder()
            ->field('name')->equals(new Regex($name, 'i'));

        if ($exclude) {
            $qb->field('_id')->notIn($exclude);
        }

        return $qb->getQuery()
            ->execute()
        ;
    }

    public function upPersonWithRole(PersonInterface $person, RoleInterface $role, MultimediaObject $multimediaObject): MultimediaObject
    {
        $multimediaObject->upPersonWithRole($person, $role);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    public function downPersonWithRole(PersonInterface $person, RoleInterface $role, MultimediaObject $multimediaObject): MultimediaObject
    {
        $multimediaObject->downPersonWithRole($person, $role);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    public function deleteRelation(PersonInterface $person, RoleInterface $role, MultimediaObject $multimediaObject): MultimediaObject
    {
        $hasBeenRemoved = $multimediaObject->removePersonWithRole($person, $role);
        if ($hasBeenRemoved) {
            $role->decreaseNumberPeopleInMultimediaObject();
            if ($this->addUserAsPerson && ($this->personalScopeRoleCode === $role->getCod()) && (null !== $person->getUser())) {
                $this->userService->removeOwnerUserFromMultimediaObject($multimediaObject, $person->getUser(), false);
            }
        }
        $this->dm->persist($multimediaObject);
        $this->dm->persist($role);
        $this->dm->flush();

        $this->dispatcher->dispatchDelete($multimediaObject, $person, $role);

        return $multimediaObject;
    }

    public function deletePerson(PersonInterface $person, bool $deleteFromUser = false): void
    {
        if (0 !== $this->repoMmobj->countByPersonId($person->getId())) {
            throw new \Exception("Couldn't remove Person with id ".$person->getId().'. There are multimedia objects with this person');
        }

        if ((null !== $user = $person->getUser()) && !$deleteFromUser) {
            throw new \Exception('Could not remove Person with id "'.$person->getId().'". There is an User with id "'.$user->getId().'" and usernname "'.$user->getUsername().'" referenced. Delete the user to delete this Person.');
        }

        $this->dm->remove($person);
        $this->dm->flush();
    }

    public function batchDeletePerson(PersonInterface $person): void
    {
        foreach ($this->repoMmobj->findByPersonId($person->getId()) as $mmobj) {
            foreach ($mmobj->getRoles() as $embeddedRole) {
                if ($mmobj->containsPersonWithRole($person, $embeddedRole)) {
                    if (!($mmobj->removePersonWithRole($person, $embeddedRole))) {
                        throw new \Exception('There was an error removing person '.$person->getId().' with role '.$embeddedRole->getCod().' in multimedia object '.$mmobj->getId());
                    }
                    $this->dispatcher->dispatchDelete($mmobj, $person, $embeddedRole);
                }
            }
            $this->dm->persist($mmobj);
        }

        $this->dm->remove($person);
        $this->dm->flush();
    }

    public function countMultimediaObjectsWithPerson(PersonInterface $person)
    {
        return $this->repoMmobj->countByPersonId($person->getId());
    }

    public function referencePersonIntoUser(User $user): User
    {
        if ($this->addUserAsPerson && (null === $person = $user->getPerson())) {
            $person = $this->createFromUser($user);

            $user->setPerson($person);
            $person->setUser($user);

            $this->dm->persist($user);
            $this->dm->persist($person);
            $this->dm->flush();
        }

        return $user;
    }

    public function getPersonFromLoggedInUser(User $loggedInUser = null): ?Person
    {
        if (null !== $loggedInUser) {
            if (null === $person = $loggedInUser->getPerson()) {
                $loggedInUser = $this->referencePersonIntoUser($loggedInUser);
                $person = $loggedInUser->getPerson();
            }

            return $person;
        }

        return null;
    }

    public function getPersonalScopeRole(): ?Role
    {
        $personalScopeRole = $this->dm->getRepository(Role::class)->findOneBy(['cod' => $this->personalScopeRoleCode]);
        if ($this->addUserAsPerson && (null === $personalScopeRole)) {
            throw new \Exception('Invalid Personal Scope Role Code: "'.$this->personalScopeRoleCode
                                 .'". There is no Role with this data. '
                                 .'Change it on parameters.yml or use default value by deleting '
                                 .'line "personal_scope_role_code: \''.$this->personalScopeRoleCode.'\'" '
                                 .'from your parameters file.');
        }

        return $personalScopeRole;
    }

    public function getPersonalScopeRoleCode(): string
    {
        return $this->personalScopeRoleCode;
    }

    public function getRoles()
    {
        $criteria = [];
        $sort = ['rank' => 1];

        return $this->repoRole->findBy($criteria, $sort);
    }

    public function removeUserFromPerson(User $user, PersonInterface $person, bool $executeFlush = true): void
    {
        $person->setUser(null);
        $this->dm->persist($person);
        if ($executeFlush) {
            $this->dm->flush();
        }
    }

    private function createFromUser(User $user)
    {
        if ($user->getEmail()) {
            if ($person = $this->repoPerson->findOneBy(['email' => $user->getEmail()])) {
                return $person;
            }
        } elseif ($person = $this->repoPerson->findOneBy(['email' => ''])) {
            return $person;
        }

        $person = new Person();

        $person->setName($user->getFullname() ?: $user->getUsername());
        $person->setEmail($user->getEmail());

        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    private function updateEmbeddedPerson(PersonInterface $person, PersonInterface $embeddedPerson): PersonInterface
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
}
