<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Services\SampleSecretsChecker;
use Symfony\Component\Dotenv\Dotenv;

/**
 * @internal
 */
#[CoversNothing]
class SampleSecretsCheckerTest extends TestCase
{
    private const VARIABLES = ['PUMUKIT_PASS', 'MONGODB_PASSWORD', 'MONGODB_URL', 'REDIS_PASSWORD', 'RABBITMQ_DEFAULT_PASS', 'MESSENGER_TRANSPORT_DSN'];

    private array $previous = [];

    protected function setUp(): void
    {
        foreach (self::VARIABLES as $name) {
            $this->previous[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previous as $name => [$server, $env, $process]) {
            unset($_SERVER[$name], $_ENV[$name]);
            if (null !== $server) {
                $_SERVER[$name] = $server;
            }
            if (null !== $env) {
                $_ENV[$name] = $env;
            }
            putenv(false === $process ? $name : $name.'='.$process);
        }
    }

    public function testRealValuesAreAccepted(): void
    {
        $this->set(['PUMUKIT_PASS' => 'x', 'MONGODB_PASSWORD' => 'x', 'MONGODB_URL' => 'mongodb://app:x@db:27017/pumukit', 'REDIS_PASSWORD' => 'x', 'RABBITMQ_DEFAULT_PASS' => 'x', 'MESSENGER_TRANSPORT_DSN' => 'amqp://app:x@rabbitmq:5672/app/messages']);

        $checker = new SampleSecretsChecker('real', 'real');

        $this->assertSame([], $checker->sampleValues());
        $this->assertSame([], $checker->warnings());
    }

    public function testVariablesThatAreNotDefinedAreNotSamples(): void
    {
        $this->assertSame([], (new SampleSecretsChecker('real', 'real'))->sampleValues());
    }

    #[DataProvider('secrets')]
    public function testSampleSecretsAreFound(string $framework, string $player, array $expected): void
    {
        $this->assertSame($expected, (new SampleSecretsChecker($framework, $player))->sampleValues());
    }

    public static function secrets(): iterable
    {
        yield 'framework' => ['881777ebf96f4b2c89e3275385bf5bfd', 'real', ['PUMUKIT_SECRET']];

        yield 'old framework' => ['ThisTokenIsNotSoSecretChangeIt', 'real', ['PUMUKIT_SECRET']];

        yield 'empty framework' => ['', 'real', ['PUMUKIT_SECRET']];

        yield 'player' => ['real', 'changeMe!', ['PUMUKITPLAYER_SECURE_SECRET']];

        yield 'empty player' => ['real', '', ['PUMUKITPLAYER_SECURE_SECRET']];
    }

    public function testAPlayerSecretThatIsNotDefinedIsFound(): void
    {
        $this->assertSame(['PUMUKITPLAYER_SECURE_SECRET'], (new SampleSecretsChecker('real', null))->sampleValues());
    }

    public function testEveryValueOfTheRepositoryIsASample(): void
    {
        $sample = (new Dotenv())->parse((string) file_get_contents(dirname(__DIR__, 5).'/.env'));
        $this->set(array_intersect_key($sample, array_flip(self::VARIABLES)));

        $found = (new SampleSecretsChecker($sample['PUMUKIT_SECRET'], $sample['PUMUKITPLAYER_SECURE_SECRET']))->sampleValues();

        $this->assertEqualsCanonicalizing(['PUMUKIT_SECRET', 'PUMUKITPLAYER_SECURE_SECRET', ...self::VARIABLES], $found);
    }

    #[DataProvider('warnings')]
    public function testWarnings(array $variables, int $expected): void
    {
        $this->set($variables);

        $this->assertCount($expected, (new SampleSecretsChecker('real', 'real'))->warnings());
    }

    public static function warnings(): iterable
    {
        yield 'mongodb without user and empty password' => [['MONGODB_URL' => 'mongodb://db:27017', 'REDIS_PASSWORD' => ''], 2];

        yield 'mongodb as root' => [['MONGODB_URL' => 'mongodb://root:x@db:27017', 'REDIS_PASSWORD' => 'x'], 1];

        yield 'mongodb as ADMIN' => [['MONGODB_URL' => 'mongodb://ADMIN:x@db:27017', 'REDIS_PASSWORD' => 'x'], 1];

        yield 'own user' => [['MONGODB_URL' => 'mongodb://app:x@db:27017', 'REDIS_PASSWORD' => 'x'], 0];
    }

    private function set(array $variables): void
    {
        foreach ($variables as $name => $value) {
            $_SERVER[$name] = $value;
        }
    }
}
