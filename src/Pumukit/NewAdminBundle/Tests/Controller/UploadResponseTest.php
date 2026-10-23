<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
#[CoversNothing]
class UploadResponseTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private MultimediaObject $multimediaObject;
    private string $tmpDir;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->dm = $container->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $roles = array_values(array_filter(
            (new \ReflectionClass(Permission::class))->getConstants(),
            static fn ($value): bool => is_string($value) && str_starts_with($value, 'ROLE_')
        ));
        $user = new User();
        $user->setUsername('uploadadmin');
        $user->setEmail('uploadadmin@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', ...$roles]);
        $this->dm->persist($user);
        $this->dm->flush();

        $factory = $container->get(FactoryService::class);
        $this->multimediaObject = $factory->createMultimediaObject($factory->createSeries(), true);

        $this->client->loginUser($user, 'pumukit');
        $this->tmpDir = sys_get_temp_dir().'/pumukit-upload-'.uniqid('', true);
        mkdir($this->tmpDir);
    }

    public function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir.'/*') ?: []);
        rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testPicUrlUploadAnswersJson(): void
    {
        $this->client->request('POST', $this->picUploadUrl(), ['url' => 'https://example.org/poster.jpg'], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['uploaded' => 'success', 'message' => 'New Pic added.'], $this->json());
    }

    public function testPicUploadRejectsFilesThatAreNotImages(): void
    {
        $this->client->request('POST', $this->picUploadUrl(), ['type' => 'url'], ['file' => $this->file('notes.png', 'plain text')], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('failed', $this->json()['uploaded']);
        $this->assertSame('Only image files are allowed.', $this->json()['message']);
    }

    public function testMaterialUploadRejectsBlockedExtensions(): void
    {
        $url = static::getContainer()->get('router')->generate('pumukitnewadmin_material_upload', ['id' => $this->multimediaObject->getId()]);
        $this->client->request('POST', $url, ['file_type' => 'file'], ['file' => $this->file('evil.php', '<?php echo 1;')], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('failed', $this->json()['uploaded']);
    }

    public function testIframeUploadsStillGetTheHtmlResponse(): void
    {
        $this->client->request('POST', $this->picUploadUrl(), ['url' => 'https://example.org/poster.jpg']);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('text/html', (string) $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString("var uploaded = 'success';", (string) $this->client->getResponse()->getContent());
    }

    private function picUploadUrl(): string
    {
        return static::getContainer()->get('router')->generate('pumukitnewadmin_mmspic_upload', ['id' => $this->multimediaObject->getId()]);
    }

    private function file(string $name, string $content): UploadedFile
    {
        $path = $this->tmpDir.'/'.$name;
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function json(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
