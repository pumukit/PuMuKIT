<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\SeriesType;

class SeriesTypeController extends AdminController
{
    public static $resourceName = 'seriestype';
    public static $repoName = 'PumukitSchemaBundle:SeriesType';

    public function __construct(DocumentManager $documentManager, PaginationService $paginationService, FactoryService $factoryService, GroupService $groupService, UserService $userService)
    {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
    }

    public function createNew()
    {
        return new SeriesType();
    }
}
