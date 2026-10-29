<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Message;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\WebTVBundle\Controller\ChatController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class ChatControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private string $url;
    private array $server;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setSeries($series);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->url = '/chat/post/'.$multimediaObject->getId();
        $this->server = ['REMOTE_ADDR' => '10.20.'.random_int(0, 255).'.'.random_int(1, 254)];
    }

    public function testAMessageIsRejectedWhenTheChatIsDisabled(): void
    {
        $this->client->request('POST', $this->url, ['name' => 'Anonymous', 'message' => 'Hello'], server: $this->server);

        $this->assertResponseStatusCodeSame(404);
        $this->assertCount(0, $this->dm->getRepository(Message::class)->findAll());
    }

    public function testAMessageCannotBePostedWithGet(): void
    {
        $this->enableChat();

        $this->client->request('GET', $this->url, ['name' => 'Anonymous', 'message' => 'Hello'], server: $this->server);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testTheMessageKeepsAHashOfTheSessionOfItsAuthor(): void
    {
        $this->enableChat();

        $this->client->request('POST', $this->url, ['name' => 'Anonymous', 'message' => 'Hello'], server: $this->server);

        $this->assertResponseIsSuccessful();
        $this->dm->clear();
        $messages = $this->dm->getRepository(Message::class)->findAll();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $messages[0]->getCookie());
    }

    public function testTooManyMessagesFromTheSameAddressAreRejected(): void
    {
        $this->enableChat();

        for ($i = 0; $i < 10; ++$i) {
            $this->client->request('POST', $this->url, ['name' => 'Anonymous', 'message' => 'Hello '.$i], server: $this->server);
            $this->assertResponseIsSuccessful();
        }

        $this->client->request('POST', $this->url, ['name' => 'Anonymous', 'message' => 'One more'], server: $this->server);

        $this->assertResponseStatusCodeSame(429);
    }

    private function enableChat(): void
    {
        $controller = static::getContainer()->get(ChatController::class);
        (new \ReflectionProperty($controller, 'pumukitLiveChatEnable'))->setValue($controller, true);
    }
}
