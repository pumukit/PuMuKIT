<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Controller;

/**
 * This interface adds the 'admin' filter to any controller implementing it.
 *
 * The 'admin' filter checks whether you have edit access to the mmobjs/series.
 * These filters are used to avoid certain mmobjs/series from appearing when executing ODM calls. (check EventListener/AdminFilterListener.php)
 * We use the implementation of before/after filters with an EventListener as in the Symfony cookbooks.
 * Symfony docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html#tag-controllers-to-be-checked
 */
interface AdminControllerInterface {}
