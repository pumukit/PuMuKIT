<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\NewAdminBundle\Form\Type\LinkType;
use Pumukit\NewAdminBundle\Form\Type\PersonType;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Person;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[CoversNothing]
class UrlFieldValidationTest extends KernelTestCase
{
    public function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get('request_stack')->push($request);
    }

    #[DataProvider('urls')]
    public function testTheLinkUrlOnlyAcceptsWebAddresses(string $url, bool $valid): void
    {
        $form = $this->form(LinkType::class, new Link());
        $form->submit(['i18n_name' => ['en' => 'Link'], 'url' => $url]);

        $this->assertSame($valid, $form->get('url')->isValid());
    }

    #[DataProvider('urls')]
    public function testThePersonWebOnlyAcceptsWebAddresses(string $url, bool $valid): void
    {
        $form = $this->form(PersonType::class, new Person());
        $form->submit(['name' => 'Person', 'web' => $url], false);

        $this->assertSame($valid, $form->get('web')->isValid());
    }

    public static function urls(): iterable
    {
        yield 'https' => ['https://example.org/page', true];

        yield 'without scheme' => ['example.org/page', true];

        yield 'javascript with a comment' => ['javascript://%0Aalert(1)', false];

        yield 'ftp' => ['ftp://example.org/file', false];
    }

    private function form(string $type, object $data): FormInterface
    {
        return static::getContainer()->get('form.factory')->create($type, $data, [
            'translator' => static::getContainer()->get('translator'),
            'locale' => 'en',
            'csrf_protection' => false,
        ]);
    }
}
