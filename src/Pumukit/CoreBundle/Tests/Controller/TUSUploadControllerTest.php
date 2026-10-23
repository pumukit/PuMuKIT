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
class TUSUploadControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $user = new User();
        $user->setUsername('tusuploader');
        $user->setEmail('tusuploader@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles([Permission::UPLOAD_INBOX]);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('rejectedMetadata')]
    public function testRejectsUploadsWhoseStoredNameIsNotTheValidatedOne(array $metadata): void
    {
        $this->createUpload($metadata);

        $this->assertResponseStatusCodeSame(400);
    }

    public static function rejectedMetadata(): array
    {
        return [
            'stored name differs from the validated one' => [['name' => 'evil.php', 'filename' => 'video.mp4', 'type' => 'video/mp4']],
            'only the stored name' => [['name' => 'evil.php']],
            'validated name is blacklisted' => [['name' => 'video.mp4', 'filename' => 'evil.php']],
            'repeated name' => [['name' => 'evil.php', 'name ' => 'video.mp4']],
            'stored type differs from the validated one' => [['name' => 'video.mp4', 'filename' => 'video.mp4', 'type' => 'text/html', 'filetype' => 'video/mp4']],
            'no file name' => [['type' => 'video/mp4']],
        ];
    }

    public function testAcceptsAConsistentUpload(): void
    {
        $this->createUpload(['name' => 'video.mp4', 'filename' => 'video.mp4', 'type' => 'video/mp4', 'filetype' => 'video/mp4']);

        $this->assertNotSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testAcceptsTheMetadataSentByUppy(): void
    {
        $this->client->request('POST', '/tus', [], [], [
            'HTTP_TUS_RESUMABLE' => '1.0.0',
            'HTTP_UPLOAD_LENGTH' => '10',
            'HTTP_UPLOAD_METADATA' => 'series bWkgY2FycGV0YQ==,name MTc5Mjc0MzI5NTEwMl9NaV92X2Rlb19maW5hbF9tcDQubXA0,type dmlkZW8vbXA0,filetype dmlkZW8vbXA0,filename MTc5Mjc0MzI5NTEwMl9NaV92X2Rlb19maW5hbF9tcDQubXA0',
        ]);

        $this->assertNotSame(400, $this->client->getResponse()->getStatusCode());
    }

    private function createUpload(array $metadata): void
    {
        $header = implode(',', array_map(
            static fn (string $key, string $value): string => trim($key).' '.base64_encode($value),
            array_keys($metadata),
            $metadata
        ));

        $this->client->request('POST', '/tus', [], [], [
            'HTTP_TUS_RESUMABLE' => '1.0.0',
            'HTTP_UPLOAD_LENGTH' => '10',
            'HTTP_UPLOAD_METADATA' => $header,
        ]);
    }
}
