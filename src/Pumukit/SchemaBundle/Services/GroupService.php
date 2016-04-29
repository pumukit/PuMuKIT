<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Group;
use Doctrine\ODM\MongoDB\DocumentManager;

class GroupService
{
    private $dm;
    private $repo;
    private $userRepo;
    private $dispatcher;

    /**
     * Constructor
     *
     * @param DocumentManager $documentManager
     * @param GroupEventDispatcherService $dispatcher
     */
    public function __construct(DocumentManager $documentManager, GroupEventDispatcherService $dispatcher)
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Group');
        $this->userRepo = $this->dm->getRepository('PumukitSchemaBundle:User');
    }

    /**
     * Create group
     *
     * @param Group $group
     * @return Group
     */
    public function create(Group $group)
    {
        if ($this->repo->findOneByKey($group->getKey())) {
            throw new \Exception('There is already a group created with key "'.$group->getKey().'".');
        }
        if ($this->repo->findOneByName($group->getName())) {
            throw new \Exception('There is already a group created with name "'.$group->getName().'".');
        }
        $this->dm->persist($group);
        $this->dm->flush();

        $this->dispatcher->dispatchCreate($group);

        return $group;
    }

    /**
     * Update group
     *
     * @param Group $group
     * @param boolean $executeFlush
     * @return Group
     */
    public function update(Group $group, $executeFlush = true)
    {
        $auxKeyGroup = $this->repo->findOneByKey($group->getKey());
        if ($auxKeyGroup) {
            if ($auxKeyGroup->getId() != $group->getId()) {
                throw new \Exception('There is already a group created with key "'.$group->getKey().'".');
            }
        }

        $auxNameGroup = $this->repo->findOneByName($group->getName());
        if ($auxNameGroup) {
            if ($auxNameGroup->getId() != $group->getId()) {
                throw new \Exception('There is already a group created with name "'.$group->getName().'".');
            }
        }

        $group->setUpdatedAt(new \Datetime('now'));
        $this->dm->persist($group);
        if ($executeFlush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchUpdate($group);

        return $group;
    }

    /**
     * Delete group
     *
     * @param Group $group
     * @param boolean $executeFlush
     */
    public function delete(Group $group, $executeFlush = true)
    {
        if (($count = $this->countUsersInGroup($group)) > 0) {
            throw new \Exception('Not allowed to delete Group "'.$group->getKey().'". '.$count.' users belong(s) to this group.');
        }
        $this->dm->remove($group);
        if ($executeFlush) $this->dm->flush();

        $this->dispatcher->dispatchDelete($group);
    }

    /**
     * Count users in group
     *
     * @param Group $group
     * @return integer
     */
    public function countUsersInGroup(Group $group)
    {
        $qb = $this->userRepo->createQueryBuilder();
        $qb->addOr($qb->expr()->field('adminGroups.id')->equals($group->getId()));
        $qb->addOr($qb->expr()->field('memberGroups.id')->equals($group->getId()));
        return $qb->count()
            ->getQuery()
            ->execute();
    }
}