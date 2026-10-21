<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

final class BlackListExtensions
{
    public static function all(): array
    {
        return [
            'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar', 'pht', 'phar',
            'sh', 'bash', 'bat', 'cmd', 'cgi', 'pl', 'py', 'pyc', 'pyo', 'rb', 'msi', 'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh', 'ps1',
            'exe', 'com', 'bin', 'app', 'elf', 'osx',
            'htaccess', 'htpasswd', 'config', 'conf', 'env', 'ini', 'log', 'sql', 'bak',
            'html', 'htm', 'xhtml', 'svg', 'swf',
        ];
    }

    public static function isBlackListed(string $extension): bool
    {
        return in_array(strtolower($extension), self::all(), true);
    }

    public static function assertNotBlackListed(string $extension): void
    {
        if (self::isBlackListed($extension)) {
            throw new \InvalidArgumentException(sprintf('File extension "%s" is not allowed for security reasons.', $extension));
        }
    }
}
