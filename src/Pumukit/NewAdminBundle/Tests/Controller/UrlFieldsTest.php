<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\MediaCreator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class UrlFieldsTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private MultimediaObject $multimediaObject;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->dm = $container->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $roles = array_values(array_filter(
            (new \ReflectionClass(Permission::class))->getConstants(),
            static fn ($value): bool => is_string($value) && str_starts_with($value, 'ROLE_')
        ));
        $user = new User();
        $user->setUsername('urlfields');
        $user->setEmail('urlfields@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', ...$roles]);
        $this->dm->persist($user);
        $this->dm->flush();

        $factory = $container->get(FactoryService::class);
        $this->multimediaObject = $factory->createMultimediaObject($factory->createSeries(), true);
        $container->get(MediaCreator::class)->createMediaFromExternalURL($this->multimediaObject, 'https://example.org/player');
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('formsWithUrlFields')]
    public function testTheFormRendersItsUrlField(string $route, array $parameters, string $field): void
    {
        $parameters = array_map(fn (string $value): string => match ($value) {
            'mm' => $this->multimediaObject->getId(),
            'series' => $this->multimediaObject->getSeries()->getId(),
            default => $value,
        }, $parameters);

        $this->client->request('GET', static::getContainer()->get('router')->generate($route, $parameters));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists($field);
    }

    public static function formsWithUrlFields(): array
    {
        return [
            'live channel' => ['pumukitnewadmin_live_create', [], '#pumukitnewadmin_live_url'],
            'person' => ['pumukitnewadmin_person_create', [], '#pumukitnewadmin_person_web'],
            'link' => ['pumukitnewadmin_link_create', ['id' => 'mm'], '#pumukitnewadmin_link_url'],
            'external player' => ['pumukitnewadmin_mms_listexternalproperties', ['series' => 'series'], '#form_url'],
            'external player update' => ['pumukitnewadmin_external_player_update', ['id' => 'mm'], '#form_url'],
        ];
    }
}
