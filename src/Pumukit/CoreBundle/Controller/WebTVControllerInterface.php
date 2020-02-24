<?php

namespace Pumukit\CoreBundle\Controller;

/**
 * This interface adds the 'WebTV' filter to any controller implementing it.
 *
 * The 'webtv' filter checks whether you have view access to the mmobjs/series.
 * These filters are used to avoid certain mmobjs/series from appearing when executing ODM calls. (check EventListener/WebTVFilterListener.php)
 * We use the implementation of before/after filters with an EventListener as in the Symfony cookbooks.
 * Symfony docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html#tag-controllers-to-be-checked
 */
interface WebTVControllerInterface
{
}
