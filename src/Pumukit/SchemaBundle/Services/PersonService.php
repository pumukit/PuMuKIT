<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\SecurityContext;

class PersonService
{
    private $dm;
    private $repo;
    private $repoMmobj;
    private $securityContext;
    private $autoPublisherRoleCode;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager, SecurityContext $securityContext, $autoPublisherRoleCode='owner')
    {
        $this->dm = $documentManager;
        $this->securityContext = $securityContext;
        $this->autoPublisherRoleCode = $autoPublisherRoleCode;
        $this->repo = $documentManager->getRepository('PumukitSchemaBundle:Person');
        $this->repoMmobj = $documentManager->getRepository('PumukitSchemaBundle:MultimediaObject');
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
     * Find person
     *
     * @param  string $id
     * @return Person
     */
    public function findPersonById($id)
    {
        return $this->repo->find($id);
    }

    /**
     * Find person by email
     *
     * @param  string $email
     * @return Person
     */
    public function findPersonByEmail($email)
    {
        return $this->repo->findOneByEmail($email);
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
            foreach ($mmobj->getAllEmbeddedPeopleByPerson($person) as $embeddedPerson) {
                $embeddedPerson = $this->updateEmbeddedPerson($person, $embeddedPerson);
            }
            $this->dm->persist($mmobj);
        }
        $this->dm->flush();

        return $person;
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
    public function createRelationPerson(Person $person, Role $role, MultimediaObject $multimediaObject)
    {
        if ($person && $role && $multimediaObject) {
            $this->dm->persist($person);
            $this->dm->flush();
            $multimediaObject->addPersonWithRole($person, $role);
            $role->increaseNumberPeopleInMultimediaObject();
            $this->dm->persist($multimediaObject);
            $this->dm->persist($role);
            $this->dm->flush();
        }

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
        return $this->repo->findByName(new \MongoRegex('/'.$name.'/i'));
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
        $flag = $multimediaObject->removePersonWithRole($person, $role);
        $role->decreaseNumberPeopleInMultimediaObject();
        $this->dm->persist($multimediaObject);
        $this->dm->persist($role);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Delete Person
     */
    public function deletePerson(Person $person, $deleteFromUser=false)
    {
        if (null !== $person) {
            if (0 !== count($this->repoMmobj->findByPersonId($person->getId()))) {
                throw new \Exception("Couldn't remove Person with id ".$person->getId().". There are multimedia objects with this person");
            }
 
            if ((null != $user = $person->getUser()) && !$deleteFromUser) {
                throw new \Exception('Could not remove Person with id "'.$person->getId().'". There is an User with id "'.$user->getId().'" and usernname "'.$user->getUsername().'" referenced. Delete the user to delete this Person.');
            }

            $this->dm->remove($person);
            $this->dm->flush();
        }
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
        if (null == $person = $user->getPerson()) {
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
     * @return Person|null
     */
    public function getPersonFromLoggedInUser()
    {
        if (null != $token = $this->securityContext->getToken()) {
            if (null != $user = $token->getUser()) {
                if (null == $person = $user->getPerson()) {
                    $user = $this->referencePersonIntoUser($user);
                    $person = $user->getPerson();
                }

                return $person;
            }
        }

        return null;
    }

    /**
     * Get Auto Publisher Role
     *
     * Gets the default role
     * to add the User as Person
     * to MultimediaObject
     *
     * @return Role
     */
    public function getAutoPublisherRole()
    {
        return $this->dm->getRepository('PumukitSchemaBundle:Role')->findOneByCod($this->autoPublisherRoleCode);
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
}
