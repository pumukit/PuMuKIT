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
     * @param Person $person
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
     * @param string $id
     * @return Person
     */
    public function findPersonById($id)
    {
        return $this->repo->find($id);
    }

    /**
     * Update update person
     *
     * @param Person $person
     * @return Person
     */
    public function updatePerson(Person $person)
    {       
        $person = $this->savePerson($person);

        foreach($this->repoMmobj->findByPersonId($person->getId()) as $mmobj){
            foreach($mmobj->getAllEmbeddedPeopleByPerson($person) as $embeddedPerson){
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
     * @param Person $person
     * @param int $limit Number of series, all by default
     * @return array
     */
    public function findSeriesWithPerson(Person $person, $limit = 0)
    {
        $mmobjs = $this->repoMmobj->findByPersonId($person->getId());

        $seriesCollection = new ArrayCollection();
        $count = 0;
        foreach($mmobjs as $mmobj){
            if ($limit !== 0){
                if ($count === $limit){
                    break;
                }
            }
            $oneseries = $mmobj->getSeries();
            if (!$seriesCollection->contains($oneseries)){
                $seriesCollection->add($oneseries);
            }
            ++$count;
        }

        return $seriesCollection;
    }

    /**
     * Create relation person
     *
     * @param Person $person
     * @param Role $role
     * @param MultimediaObject $multimediaObject
     * @return MultimediaObject
     */
    public function createRelationPerson(Person $person, Role $role, MultimediaObject $multimediaObject)
    {
        if ($person && $role && $multimediaObject){
            $this->dm->persist($person);
            $this->dm->flush();
            $multimediaObject->addPersonWithRole($person, $role);
            $this->dm->persist($multimediaObject);
            $this->dm->flush();
        }

        return $multimediaObject;
    }

    /**
     * Update embedded person
     *
     * @param Person $person
     * @param EmbeddedPerson $embeddedPerson
     * @return EmbeddedPerson
     */
    private function updateEmbeddedPerson(Person $person, EmbeddedPerson $embeddedPerson)
    {
        if (null !== $person){
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