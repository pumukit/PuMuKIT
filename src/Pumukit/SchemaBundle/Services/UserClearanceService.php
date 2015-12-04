<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\UserClearance;
use Pumukit\SchemaBundle\Security\Clearance;

class UserClearanceService
{
    private $dm;
    private $repo;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:UserClearance');
    }

    /**
     * Update User Clearance
     *
     * @param UserClearance $userClearance
     */
    public function update(UserClearance $userClearance)
    {
        if ($userClearance->isDefault()) {
            $this->repo->changeDefault();
        }

        $this->dm->persist($userClearance);
        $this->dm->flush();

        return $userClearance;
    }

    /**
     * Add clearance
     *
     * @param UserClearance $userClearance
     * @param string $clearance
     * @return UserClearance
     */
    public function addClearance(UserClearance $userClearance, $clearance='')
    {
        if (array_key_exists($clearance, Clearance::$clearanceDescription)) {
            $userClearance->addClearance($clearance);
            $this->dm->persist($userClearance);
            $this->dm->flush();
        }

        return $userClearance;
    }

    /**
     * Remove clearance
     *
     * @param UserClearance $userClearance
     * @param string $clearance
     * @return UserClearance
     */
    public function removeClearance(UserClearance $userClearance, $clearance='')
    {
        if ($userClearance->containsClearance($clearance)) {
            $userClearance->removeClearance($clearance);
            $this->dm->persist($userClearance);
            $this->dm->flush();
        }

        return $userClearance;
    }
}