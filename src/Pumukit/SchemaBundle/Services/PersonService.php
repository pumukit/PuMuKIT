<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Collections\ArrayCollection;

class PersonService
{
    private $dm;
    private $repo;
    private $repoMmobj;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
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
            $this->dm->persist($multimediaObject);
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
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Delete Person
     */
    public function deletePerson(Person $person)
    {
        if (null !== $person) {
            if (0 !== count($this->repoMmobj->findByPersonId($person->getId()))) {
                throw new \Exception("Couldn't remove Person with id ".$person->getId().". There are multimedia objects with this person");
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
