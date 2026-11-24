<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Controller;

use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Event\AnnotationsEvents;
use Pumukit\SchemaBundle\Event\AnnotationsUpdateEvent;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class AnnotationsAPIControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private User $personalUser;
    private User $globalUser;
    private MultimediaObject $own;
    private MultimediaObject $other;
    private MultimediaObject $public;
    private Annotation $otherAnnotation;
    private Annotation $ownAnnotation;
    private Annotation $publicAnnotation;
    private array $updatedObjects = [];

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $this->personalUser = $this->user('personal', [PermissionProfile::SCOPE_PERSONAL, 'ROLE_ACCESS_MULTIMEDIA_SERIES']);
        $this->globalUser = $this->user('global', [PermissionProfile::SCOPE_GLOBAL, 'ROLE_ACCESS_MULTIMEDIA_SERIES']);

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $webTV = new Tag();
        $webTV->setCod('PUCHWEBTV');
        $this->dm->persist($webTV);

        $this->own = $this->multimediaObject($series, 1);
        $this->own->setProperty('owners', [$this->personalUser->getId()]);
        $this->other = $this->multimediaObject($series, 2);
        $this->public = $this->multimediaObject($series, 3);
        $this->public->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->public->addTag($webTV);
        $this->dm->flush();

        $this->ownAnnotation = $this->annotation($this->own);
        $this->otherAnnotation = $this->annotation($this->other);
        $this->publicAnnotation = $this->annotation($this->public);
        $this->dm->flush();

        static::getContainer()->get('event_dispatcher')->addListener(AnnotationsEvents::UPDATE, function (AnnotationsUpdateEvent $event): void {
            $this->updatedObjects[] = (string) $event->getMultimediaObject();
        });
    }

    public function testAnonymousCanReadTheAnnotationsOfAPublicObject(): void
    {
        $this->client->request('GET', '/annotation/annotations.json', ['episode' => $this->public->getId()]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($this->publicAnnotation->getId(), (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/annotation/'.$this->publicAnnotation->getId().'.json');

        $this->assertResponseIsSuccessful();
    }

    public function testAnonymousCannotReadTheAnnotationsOfAPrivateObject(): void
    {
        $this->client->request('GET', '/annotation/annotations.json', ['episode' => $this->other->getId()]);
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/annotation/'.$this->otherAnnotation->getId().'.json');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnUnknownEpisodeIsNotFound(): void
    {
        $this->client->request('GET', '/annotation/annotations.json', ['episode' => (string) new ObjectId()]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testOnlyTheGlobalScopeListsTheAnnotationsOfEveryObject(): void
    {
        $this->client->request('GET', '/annotation/annotations.json');
        $this->assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->personalUser, 'pumukit');
        $this->client->request('GET', '/annotation/annotations.json');
        $this->assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->globalUser, 'pumukit');
        $this->client->request('GET', '/annotation/annotations.json');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($this->otherAnnotation->getId(), (string) $this->client->getResponse()->getContent());
    }

    public function testAPersonalUserCannotChangeTheAnnotationsOfAnObjectItDoesNotOwn(): void
    {
        $this->client->loginUser($this->personalUser, 'pumukit');

        $this->client->request('PUT', '/annotation/', ['episode' => $this->other->getId(), 'type' => 'paella/marks', 'value' => '{}']);
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('PUT', '/annotation/'.$this->otherAnnotation->getId(), ['value' => 'changed']);
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('DELETE', '/annotation/'.$this->otherAnnotation->getId());
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('DELETE', '/annotation/reset/'.$this->other->getId());
        $this->assertResponseStatusCodeSame(403);

        $this->dm->clear();
        $annotations = $this->dm->getRepository(Annotation::class)->findBy(['multimediaObject' => new ObjectId($this->other->getId())]);
        $this->assertCount(1, $annotations);
        $this->assertSame('original', $annotations[0]->getValue());
        $this->assertSame([], $this->updatedObjects);
    }

    public function testAPersonalUserChangesTheAnnotationsOfItsOwnObject(): void
    {
        $this->client->loginUser($this->personalUser, 'pumukit');

        $this->client->request('PUT', '/annotation/', ['episode' => $this->own->getId(), 'type' => 'paella/marks', 'value' => '{}']);
        $this->assertResponseIsSuccessful();

        $this->client->request('PUT', '/annotation/'.$this->ownAnnotation->getId(), ['value' => 'changed']);
        $this->assertResponseIsSuccessful();

        $this->client->request('DELETE', '/annotation/'.$this->ownAnnotation->getId());
        $this->assertResponseIsSuccessful();

        $this->client->request('DELETE', '/annotation/reset/'.$this->own->getId());
        $this->assertResponseIsSuccessful();

        $this->dm->clear();
        $this->assertSame([], $this->dm->getRepository(Annotation::class)->findBy(['multimediaObject' => new ObjectId($this->own->getId())]));
        $this->assertSame(array_fill(0, 4, $this->own->getId()), $this->updatedObjects);
    }

    private function user(string $username, array $roles): User
    {
        $person = new Person();
        $person->setName($username);
        $this->dm->persist($person);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@pumukit.local');
        $user->setEnabled(true);
        $user->setRoles($roles);
        $user->setPerson($person);
        $person->setUser($user);
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    private function multimediaObject(Series $series, int $numericalId): MultimediaObject
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID($numericalId);
        $multimediaObject->setSeries($series);
        $multimediaObject->setStatus(MultimediaObject::STATUS_BLOCKED);
        $this->dm->persist($multimediaObject);

        return $multimediaObject;
    }

    private function annotation(MultimediaObject $multimediaObject): Annotation
    {
        $annotation = new Annotation();
        $annotation->setMultimediaObject(new ObjectId($multimediaObject->getId()));
        $annotation->setType('paella/marks');
        $annotation->setValue('original');
        $annotation->setInPoint(0);
        $annotation->setOutPoint(100);
        $annotation->setLength(0);
        $annotation->setPrivate(false);
        $annotation->setCreated(new \DateTime());
        $annotation->setUserId('test');
        $annotation->setSession('test');
        $this->dm->persist($annotation);

        return $annotation;
    }
}
