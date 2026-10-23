<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class InlineJavascriptTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $roles = array_values(array_filter(
            (new \ReflectionClass(Permission::class))->getConstants(),
            static fn ($value): bool => is_string($value) && str_starts_with($value, 'ROLE_')
        ));
        $user = new User();
        $user->setUsername('inlinejs');
        $user->setEmail('inlinejs@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', ...$roles]);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('migratedPages')]
    public function testThePageHasNoInlineJavascript(string $route): void
    {
        $this->client->request('GET', static::getContainer()->get('router')->generate($route));

        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();

        preg_match_all('/<[a-z][^>]*\son[a-z]+\s*=/i', $html, $handlers);
        $this->assertSame([], $handlers[0]);

        preg_match_all('#<script\b(?![^>]*\bsrc=)(?![^>]*type="importmap")[^>]*>(.*?)</script>#is', $html, $scripts);
        $inline = array_values(array_filter(array_map('trim', $scripts[1]), static fn (string $code): bool => '' !== $code && "import 'pumukit';" !== $code));
        $this->assertSame([], $inline);
    }

    public static function migratedPages(): array
    {
        return [
            'roles' => ['pumukitnewadmin_role_index'],
            'series types' => ['pumukitnewadmin_seriestype_index'],
            'live channels' => ['pumukitnewadmin_live_index'],
            'legacy events' => ['pumukitnewadmin_event_index'],
            'users' => ['pumukitnewadmin_user_index'],
            'groups' => ['pumukitnewadmin_group_index'],
            'people' => ['pumukitnewadmin_person_index'],
            'permission profiles' => ['pumukitnewadmin_permissionprofile_index'],
            'tags' => ['pumukitnewadmin_tag_index'],
        ];
    }
}
