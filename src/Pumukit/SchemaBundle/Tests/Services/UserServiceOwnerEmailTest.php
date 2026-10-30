<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Event\MultimediaObjectAddOwnerEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @internal
 */
#[CoversNothing]
class UserServiceOwnerEmailTest extends PumukitTestCase
{
    private EventDispatcher $dispatcher;
    private TokenStorage $tokenStorage;
    private array $events = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIA_OBJECT_ADD_OWNER, function (MultimediaObjectAddOwnerEvent $event): void {
            $this->events[] = $event;
        });
        $this->tokenStorage = new TokenStorage();
    }

    public function testAddingAnotherUserAsOwnerDispatchesTheEmailEvent(): void
    {
        $owner = $this->user('owner');
        $coOwner = $this->user('coowner');
        $this->login($owner);

        $this->userService(true)->addOwnerUserToMultimediaObject($this->multimediaObject(), $coOwner);

        $this->assertCount(1, $this->events);
        $this->assertSame($owner, $this->events[0]->getUser());
        $this->assertSame($coOwner, $this->events[0]->getCoOwner());
    }

    public function testNoEmailEventWhenTheOptionIsDisabled(): void
    {
        $this->login($this->user('owner'));

        $this->userService(false)->addOwnerUserToMultimediaObject($this->multimediaObject(), $this->user('coowner'));

        $this->assertSame([], $this->events);
    }

    public function testNoEmailEventWithoutALoggedUser(): void
    {
        $multimediaObject = $this->userService(true)->addOwnerUserToMultimediaObject($this->multimediaObject(), $this->user('coowner'));

        $this->assertSame([], $this->events);
        $this->assertCount(1, $multimediaObject->getProperty('owners'));
    }

    private function userService(bool $sendEmail): UserService
    {
        $permissionService = new PermissionService($this->dm);

        return new UserService(
            $this->dm,
            new UserEventDispatcherService($this->dispatcher),
            $permissionService,
            new PermissionProfileService($this->dm, new PermissionProfileEventDispatcherService($this->dispatcher), $permissionService),
            new MultimediaObjectEventDispatcherService($this->dispatcher),
            $this->tokenStorage,
            false,
            $sendEmail
        );
    }

    private function login(User $user): void
    {
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }

    private function user(string $username): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@pumukit.local');
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    private function multimediaObject(): MultimediaObject
    {
        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setSeries($series);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }
}
