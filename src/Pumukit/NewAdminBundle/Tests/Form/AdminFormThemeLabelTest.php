<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @internal
 */
#[CoversNothing]
class AdminFormThemeLabelTest extends KernelTestCase
{
    public function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get('request_stack')->push($request);
    }

    public function testALabelWithHtmlCharactersIsEscaped(): void
    {
        $form = static::getContainer()->get('form.factory')->createBuilder(FormType::class)
            ->add('name', TextType::class, ['label' => '<img src=x onerror=alert(1)>'])
            ->getForm()
        ;

        $html = $this->renderLabel($form->createView(), 'name');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testTheCopyrightLabelKeepsItsInformationLink(): void
    {
        $user = new User();
        $user->setUsername('labels');
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        static::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'pumukit', $user->getRoles()));

        $type = static::getContainer()->get(MultimediaObjectMetaType::class);
        (new \ReflectionProperty($type, 'copyrightInfoUrl'))->setValue($type, 'https://example.org/copyright');

        $form = static::getContainer()->get('form.factory')->create(MultimediaObjectMetaType::class, new MultimediaObject(), [
            'translator' => static::getContainer()->get('translator'),
            'locale' => 'en',
        ]);

        $html = $this->renderLabel($form->createView(), 'copyright');

        $this->assertStringContainsString('<a href="https://example.org/copyright" target="__blank">', $html);
    }

    private function renderLabel(FormView $view, string $field): string
    {
        $twig = static::getContainer()->get('twig');
        $template = $twig->createTemplate("{% form_theme form '@PumukitNewAdmin/Themes/fields.html.twig' %}{{ form_label(form[field]) }}");

        return $template->render(['form' => $view, 'field' => $field]);
    }
}
