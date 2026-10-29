<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[CoversNothing]
class TagCatalogueServiceTest extends PumukitTestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(1)>';
    private const ESCAPED = '&lt;img src=x onerror=alert(1)&gt;';

    private TagCatalogueService $service;
    private Session $session;
    private MultimediaObject $multimediaObject;

    public function setUp(): void
    {
        parent::setUp();

        $role = new Role();
        $role->setCod('actor');
        $this->dm->persist($role);

        $person = new Person();
        $person->setName(self::PAYLOAD);
        $this->dm->persist($person);

        $series = new Series();
        $series->setNumericalID(1);
        $series->setTitle(self::PAYLOAD);
        $this->dm->persist($series);

        $this->multimediaObject = new MultimediaObject();
        $this->multimediaObject->setNumericalID(1);
        $this->multimediaObject->setTitle(self::PAYLOAD);
        $this->multimediaObject->setSeries($series);
        $this->multimediaObject->addPersonWithRole($person, $role);
        $this->dm->persist($this->multimediaObject);
        $this->dm->flush();

        $this->service = static::getContainer()->get(TagCatalogueService::class);
        $this->session = new Session(new MockArraySessionStorage());
    }

    public function testTheTitleIsEscaped(): void
    {
        $this->assertSame(self::ESCAPED, $this->service->renderField($this->multimediaObject, $this->session, 'title'));
    }

    public function testTheSeriesTitleIsEscapedInsideItsLink(): void
    {
        $html = $this->service->renderField($this->multimediaObject, $this->session, 'seriesTitle');

        $this->assertStringContainsString('<a href=', $html);
        $this->assertStringContainsString(self::ESCAPED, $html);
        $this->assertStringNotContainsString(self::PAYLOAD, $html);
    }

    public function testThePeopleOfARoleAreEscaped(): void
    {
        $html = $this->service->renderField($this->multimediaObject, $this->session, 'role.actor');

        $this->assertStringContainsString(self::ESCAPED, $html);
        $this->assertStringNotContainsString(self::PAYLOAD, $html);
    }

    public function testTheCriteriaColumnIsEscaped(): void
    {
        $this->session->set('UNESCO/criteria', ['title.en' => 'x']);

        $this->assertSame(self::ESCAPED, $this->service->renderField($this->multimediaObject, $this->session, 'criteria'));
    }
}
