<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\SeriesType;

class SeriesTypeController extends AdminController
{
    public static $resourceName = 'seriestype';
    public static $repoName = 'PumukitSchemaBundle:SeriesType';

    public function createNew()
    {
        return new SeriesType();
    }
}
