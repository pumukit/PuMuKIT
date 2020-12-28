<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

/**
 * This interface adds the NewAdmin filter to any controller implementing it.
 *
 * This filter is used to avoid certain mmobjs/series from appearing when executing ODM calls. (check EventListener/FilterListener.php)
 * We use the implementation of before/after filters with an EventListener as in the Symfony cookbooks.
 * Symfony docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html#tag-controllers-to-be-checked
 */
interface NewAdminControllerInterface
{
}
