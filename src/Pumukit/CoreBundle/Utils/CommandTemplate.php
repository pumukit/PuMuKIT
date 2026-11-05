<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

/**
 * Turns a command template into the argument list of a process, so no shell ever reads a value.
 *
 * The template is split before replacing its variables: a value is part of a single argument, whatever it contains.
 */
final class CommandTemplate
{
    private const SHELL_SYNTAX = '|&;<>$`()\*?~#';
    private const SHELL_SYNTAX_IN_DOUBLE_QUOTES = '$`\\';

    /**
     * @param array<string, float|int|string> $variables
     *
     * @return list<string>
     */
    public static function toArgv(string $template, array $variables = []): array
    {
        return array_map(
            static fn (string $argument): string => strtr($argument, $variables),
            self::split($template)
        );
    }

    /**
     * @return list<string>
     */
    private static function split(string $template): array
    {
        $arguments = [];
        $argument = null;
        $quote = null;

        foreach (str_split($template) as $char) {
            if (null !== $quote) {
                if ($char === $quote) {
                    $quote = null;

                    continue;
                }
                if ('"' === $quote && str_contains(self::SHELL_SYNTAX_IN_DOUBLE_QUOTES, $char)) {
                    throw self::shellSyntax($template, $char);
                }
                $argument .= $char;

                continue;
            }

            if ('"' === $char || "'" === $char) {
                $quote = $char;
                $argument ??= '';

                continue;
            }

            if (ctype_space($char)) {
                if (null !== $argument) {
                    $arguments[] = $argument;
                    $argument = null;
                }

                continue;
            }

            if (str_contains(self::SHELL_SYNTAX, $char)) {
                throw self::shellSyntax($template, $char);
            }
            $argument .= $char;
        }

        if (null !== $quote) {
            throw new \InvalidArgumentException(sprintf('The command "%s" has an unclosed quote.', $template));
        }

        if (null !== $argument) {
            $arguments[] = $argument;
        }

        if ([] === $arguments) {
            throw new \InvalidArgumentException('The command is empty.');
        }

        return $arguments;
    }

    private static function shellSyntax(string $template, string $char): \InvalidArgumentException
    {
        return new \InvalidArgumentException(sprintf('The command "%s" runs without a shell and cannot use "%s": move that logic to a script.', $template, $char));
    }
}
