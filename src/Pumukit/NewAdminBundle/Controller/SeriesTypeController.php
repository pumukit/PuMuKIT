<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\SeriesType;

class SeriesTypeController extends AdminController
{
    public static $resourceName = 'seriestype';
    public static $repoName = SeriesType::class;

    public function createNew(): SeriesType
    {
        return new SeriesType();
    }
}
