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
        $default = $this->checkDefault($userClearance);

        $this->dm->persist($userClearance);
        $this->dm->flush();

        return $userClearance;
    }

    /**
     * Check default user clearance
     *
     * Checks if there is any change in 'default' property
     * If there is no UserClearance as default,
     * calls setDefaultUserClearance.
     *
     * @param UserClearance $userClearance
     */
    public function checkDefault(UserClearance $userClearance)
    {
        if ($userClearance->isDefault()) {
            $this->repo->changeDefault();
        }

        $default = $this->repo->findOneByDefault(true);
        if ((null == $default) || (!$default->isDefault())) {
            $default = $this->setDefaultUserClearance();
        }

        return $default;
    }

    /**
     * Set default user clearance
     *
     * Set as default user clearance
     * the one with less clearances
     *
     * @return UserClearance
     */
    public function setDefaultUserClearance()
    {
        $default = $this->repo->findDefaultCandidate();

        if (null == $default) return false;

        $default->setDefault(true);
        $this->dm->persist($default);
        $this->dm->flush();

        return $default;
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