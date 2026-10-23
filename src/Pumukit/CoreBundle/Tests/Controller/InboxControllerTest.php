<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Controller;

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
class InboxControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('inboxuploader');
        $user->setEmail('inboxuploader@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles([Permission::UPLOAD_INBOX]);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('importsOutsideTheInbox')]
    public function testRefusesToImportFilesOutsideTheInbox(array $parameters): void
    {
        $this->client->request('POST', '/dispatchImport', $parameters);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['success' => false], json_decode((string) $this->client->getResponse()->getContent(), true));
    }

    public static function importsOutsideTheInbox(): array
    {
        return [
            'file name traversal' => [['fileName' => '../../../../etc/passwd', 'series' => 'folder']],
            'absolute file name' => [['fileName' => '/etc/passwd', 'series' => 'folder']],
            'series traversal' => [['fileName' => 'passwd', 'series' => '../../../../etc']],
            'missing parameters' => [[]],
        ];
    }
}
