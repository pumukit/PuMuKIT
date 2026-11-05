<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

final class SampleSecretsChecker
{
    private const SAMPLE_FRAMEWORK_SECRETS = ['', '881777ebf96f4b2c89e3275385bf5bfd', 'ThisTokenIsNotSoSecretChangeIt'];
    private const SAMPLE_PLAYER_SECRETS = ['', 'changeMe!'];
    private const SAMPLE_VARIABLES = [
        'PUMUKIT_PASS' => ['admin'],
        'MONGODB_PASSWORD' => ['pumukit'],
        'REDIS_PASSWORD' => ['pumukit'],
        'RABBITMQ_DEFAULT_PASS' => ['pumukit', 'guest'],
    ];
    private const SAMPLE_URL_PASSWORDS = [
        'MONGODB_URL' => ['pumukit'],
        'MESSENGER_TRANSPORT_DSN' => ['pumukit', 'guest'],
    ];
    private const ADMINISTRATOR_USERS = ['root', 'admin', 'administrator'];

    public function __construct(
        private readonly string $frameworkSecret,
        private readonly ?string $playerSecret,
    ) {}

    public function sampleValues(): array
    {
        $found = [];
        if (in_array($this->frameworkSecret, self::SAMPLE_FRAMEWORK_SECRETS, true)) {
            $found[] = 'PUMUKIT_SECRET';
        }
        if (in_array((string) $this->playerSecret, self::SAMPLE_PLAYER_SECRETS, true)) {
            $found[] = 'PUMUKITPLAYER_SECURE_SECRET';
        }
        foreach (self::SAMPLE_VARIABLES as $name => $samples) {
            if (in_array($this->variable($name), $samples, true)) {
                $found[] = $name;
            }
        }
        foreach (self::SAMPLE_URL_PASSWORDS as $name => $samples) {
            if (in_array(parse_url($this->variable($name), PHP_URL_PASS), $samples, true)) {
                $found[] = $name;
            }
        }

        return $found;
    }

    public function warnings(): array
    {
        $warnings = [];
        $user = parse_url($this->variable('MONGODB_URL'), PHP_URL_USER);
        if (!is_string($user) || '' === $user) {
            $warnings[] = 'MONGODB_URL has no user: MongoDB should require authentication.';
        } elseif (in_array(strtolower($user), self::ADMINISTRATOR_USERS, true)) {
            $warnings[] = sprintf('MONGODB_URL connects as "%s": use a user with readWrite on the database.', $user);
        }
        if ('' === $this->variable('REDIS_PASSWORD')) {
            $warnings[] = 'REDIS_PASSWORD is empty: Valkey should require authentication.';
        }

        return $warnings;
    }

    private function variable(string $name): string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return is_string($value) ? $value : '';
    }
}
