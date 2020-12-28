<?php

declare(strict_types=1);

namespace Pumukit\InspectionBundle\Utils;

class TestCommand
{
    public static function commandExists($command): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $testCommand = 'which ';
        $os = strtolower(PHP_OS);
        if (0 === strpos($os, 'win')) {
            $testCommand = 'where ';
        }
        $command = escapeshellcmd($command);
        exec($testCommand.$command.' 2>&1', $output, $code);

        return 0 === $code && count($output) > 0;
    }
}
