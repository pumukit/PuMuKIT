<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\DependencyInjection;

use Monolog\Handler\RotatingFileHandler;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
#[CoversNothing]
final class LogRetentionTest extends KernelTestCase
{
    private ?string $previous = null;

    protected function setUp(): void
    {
        $this->previous = $_ENV['PUMUKIT_LOG_MAX_FILES'] ?? null;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['PUMUKIT_LOG_MAX_FILES'], $_SERVER['PUMUKIT_LOG_MAX_FILES']);
        if (null !== $this->previous) {
            $_ENV['PUMUKIT_LOG_MAX_FILES'] = $this->previous;
            $_SERVER['PUMUKIT_LOG_MAX_FILES'] = $this->previous;
        }
    }

    public function testRotatingLogsKeepFifteenFilesWhenTheVariableIsNotDefined(): void
    {
        unset($_ENV['PUMUKIT_LOG_MAX_FILES'], $_SERVER['PUMUKIT_LOG_MAX_FILES']);

        self::assertSame(15, $this->maxFiles());
    }

    public function testRotatingLogsKeepTheFilesSetInTheVariable(): void
    {
        $_ENV['PUMUKIT_LOG_MAX_FILES'] = '7';
        $_SERVER['PUMUKIT_LOG_MAX_FILES'] = '7';

        self::assertSame(7, $this->maxFiles());
    }

    private function maxFiles(): int
    {
        self::bootKernel();
        $handler = self::getContainer()->get('monolog.handler.debug_log');
        self::assertInstanceOf(RotatingFileHandler::class, $handler);

        return (new \ReflectionProperty(RotatingFileHandler::class, 'maxFiles'))->getValue($handler);
    }
}
