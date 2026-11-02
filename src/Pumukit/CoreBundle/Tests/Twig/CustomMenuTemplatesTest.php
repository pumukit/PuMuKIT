<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
#[CoversNothing]
class CustomMenuTemplatesTest extends KernelTestCase
{
    public function testTheTwigGlobalHasTheTemplatesAddedByTheBundles(): void
    {
        $container = static::getContainer();
        $globals = $container->get('twig')->getGlobals();

        $this->assertSame($container->getParameter('pumukit.custom_menu_templates'), $globals['custom_menu_templates']);
    }
}
