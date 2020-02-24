<?php

namespace Pumukit\CoreBundle\Controller;

/**
 * This interface adds the 'personal' filter to any controller implementing it.
 *
 * The 'personal' filter checks whether you have access to the mmobjs/series or not, an '$or' of the 'personal' and the 'admin' filters.
 * These filters are used to avoid certain mmobjs/series from appearing when executing ODM calls. (check EventListener/PersonalFilterListener.php)
 * We use the implementation of before/after filters with an EventListener as in the Symfony cookbooks.
 * Symfony docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html#tag-controllers-to-be-checked
 */
interface PersonalControllerInterface
{
}
