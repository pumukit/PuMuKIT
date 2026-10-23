<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
#[CoversNothing]
class TestDatabaseGuardTest extends KernelTestCase
{
    public function testRefusesADatabaseWithoutTheTestSuffix(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refusing to clear the "pumukit" database');

        PumukitTestCase::ensureTestDatabase($this->documentManager('pumukit'));
    }

    public function testAcceptsTheTestDatabase(): void
    {
        PumukitTestCase::ensureTestDatabase($this->documentManager('pumukit_test'));

        $this->addToAssertionCount(1);
    }

    public function testTheTestEnvironmentUsesTheTestDatabase(): void
    {
        self::bootKernel(['environment' => 'test']);

        $database = self::getContainer()->get('doctrine_mongodb.odm.document_manager')->getConfiguration()->getDefaultDB();

        $this->assertStringEndsWith('_test', (string) $database);
    }

    private function documentManager(string $database): DocumentManager
    {
        $configuration = new Configuration();
        $configuration->setDefaultDB($database);

        $documentManager = $this->createStub(DocumentManager::class);
        $documentManager->method('getConfiguration')->willReturn($configuration);

        return $documentManager;
    }
}
