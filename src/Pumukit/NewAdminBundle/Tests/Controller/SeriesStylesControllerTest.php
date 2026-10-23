<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class SeriesStylesControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
        $this->dm->getDocumentCollection(SeriesStyle::class)->deleteMany([]);

        $user = new User();
        $user->setUsername('seriesstyles');
        $user->setEmail('seriesstyles@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ACCESS_SERIES_STYLE']);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    public function testTheStyleTextIsShownAsTheTextareaValue(): void
    {
        $text = "body > p { color: red; }\n</textarea><b id=\"injected\">x</b>";
        $style = new SeriesStyle();
        $style->setName('Style');
        $style->setText($text);
        $this->dm->persist($style);
        $this->dm->flush();

        $crawler = $this->client->request('GET', static::getContainer()->get('router')->generate('pumukit_newadmin_series_styles_show', ['id' => $style->getId()]));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#injected');
        $this->assertSame($text, $crawler->filter('textarea[name="style_text"]')->text(null, false));
    }
}
