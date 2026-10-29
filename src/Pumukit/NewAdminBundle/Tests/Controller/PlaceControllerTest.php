<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class PlaceControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private User $user;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $this->user = new User();
        $this->user->setUsername('places');
        $this->user->setEmail('places@pumukit.local');
        $this->user->setPassword('unused');
        $this->user->setEnabled(true);
        $this->user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($this->user);
        $this->dm->flush();

        $this->client->loginUser($this->user, 'pumukit');
    }

    public function testTheIndexWithoutThePlacesTagExplainsWhy(): void
    {
        $this->client->request('GET', $this->url('pumukitnewadmin_places_index'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.not-configured', 'PLACES');
        $this->assertSelectorNotExists('[data-controller="admin--places"]');
    }

    public function testTheParentListWithoutThePlacesTagIsEmpty(): void
    {
        $this->client->request('GET', $this->url('pumukitnewadmin_places_parent'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.load_children');
    }

    public function testCreatingAPlaceWithoutThePlacesTagIsNotFound(): void
    {
        $this->client->request('GET', $this->url('pumukitnewadmin_places_create'));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheIndexWithThePlacesTagListsThePlaces(): void
    {
        $places = new Tag();
        $places->setCod('PLACES');
        $places->setTitle('PLACES', 'en');
        $this->dm->persist($places);

        $place = new Tag();
        $place->setCod('PLACE1');
        $place->setTitle('Main campus', 'en');
        $place->setParent($places);
        $this->dm->persist($place);
        $this->dm->flush();

        $this->client->request('GET', $this->url('pumukitnewadmin_places_index'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.not-configured');
        $this->assertSelectorExists('[data-controller="admin--places"]');
        $this->assertSelectorTextContains('.parent-list', 'Main campus');
    }

    private function url(string $route): string
    {
        return static::getContainer()->get('router')->generate($route);
    }
}
