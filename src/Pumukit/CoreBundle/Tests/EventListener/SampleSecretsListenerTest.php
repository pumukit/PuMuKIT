<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\EventListener\SampleSecretsListener;
use Pumukit\CoreBundle\Services\SampleSecretsChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
#[CoversNothing]
class SampleSecretsListenerTest extends TestCase
{
    public function testARequestFailsInProduction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PUMUKIT_SECRET');

        $this->listener('prod')->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testTheMessageDoesNotShowTheValue(): void
    {
        try {
            $this->listener('prod')->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
            $this->fail('It should have failed');
        } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('changeMe', $exception->getMessage());
        }
    }

    public function testASubRequestDoesNotFail(): void
    {
        $this->listener('prod')->onKernelRequest($this->requestEvent(HttpKernelInterface::SUB_REQUEST));

        $this->addToAssertionCount(1);
    }

    public function testOtherEnvironmentsDoNotFail(): void
    {
        $this->listener('dev')->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
        $this->listener('test')->onConsoleCommand($this->commandEvent('pumukit:init:repo'));

        $this->addToAssertionCount(2);
    }

    public function testRealValuesDoNotFail(): void
    {
        $names = ['PUMUKIT_PASS', 'MONGODB_PASSWORD', 'MONGODB_URL', 'REDIS_PASSWORD', 'RABBITMQ_DEFAULT_PASS', 'MESSENGER_TRANSPORT_DSN'];
        $server = $_SERVER;
        $env = $_ENV;
        $process = array_map(getenv(...), array_combine($names, $names));
        foreach ($names as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
        }

        try {
            $listener = new SampleSecretsListener(new SampleSecretsChecker('real', 'real'), 'prod');
            $listener->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
        } finally {
            $_SERVER = $server;
            $_ENV = $env;
            foreach ($process as $name => $value) {
                putenv(false === $value ? $name : $name.'='.$value);
            }
        }

        $this->addToAssertionCount(1);
    }

    #[DataProvider('blockedCommands')]
    public function testCommandsFailInProduction(string $name): void
    {
        $this->expectException(\RuntimeException::class);

        $this->listener('prod')->onConsoleCommand($this->commandEvent($name));
    }

    public static function blockedCommands(): iterable
    {
        yield ['pumukit:init:repo'];

        yield ['pumukit:encoder:job'];

        yield ['messenger:consume'];

        yield ['pumukit:check:secrets:other'];
    }

    #[DataProvider('buildCommands')]
    public function testBuildCommandsDoNotFail(string $name): void
    {
        $this->listener('prod')->onConsoleCommand($this->commandEvent($name));

        $this->addToAssertionCount(1);
    }

    public static function buildCommands(): iterable
    {
        yield ['cache:clear'];

        yield ['cache:warmup'];

        yield ['assets:install'];

        yield ['asset-map:compile'];

        yield ['importmap:install'];

        yield ['secrets:set'];

        yield ['lint:twig'];

        yield ['list'];

        yield ['pumukit:check:secrets'];
    }

    private function listener(string $environment): SampleSecretsListener
    {
        return new SampleSecretsListener(new SampleSecretsChecker('881777ebf96f4b2c89e3275385bf5bfd', 'changeMe!'), $environment);
    }

    private function requestEvent(int $type): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), Request::create('/'), $type);
    }

    private function commandEvent(string $name): ConsoleCommandEvent
    {
        return new ConsoleCommandEvent(new Command($name), new ArrayInput([]), new NullOutput());
    }
}
