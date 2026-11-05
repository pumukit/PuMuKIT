<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Utils;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Utils\CommandTemplate;

/**
 * @internal
 */
#[CoversNothing]
class CommandTemplateTest extends TestCase
{
    public function testSplitsTheTemplateIntoArguments(): void
    {
        $this->assertSame(['ffprobe', '-v', 'quiet', '/a.mp4'], CommandTemplate::toArgv('ffprobe  -v quiet {{file}}', ['{{file}}' => '/a.mp4']));
    }

    #[DataProvider('templatesOfAFile')]
    public function testAQuotedVariableIsTheSameAsAnUnquotedOne(string $template): void
    {
        $this->assertSame(['tool', '/a.mp4'], CommandTemplate::toArgv($template, ['{{file}}' => '/a.mp4']));
    }

    public static function templatesOfAFile(): iterable
    {
        yield 'unquoted' => ['tool {{file}}'];

        yield 'double quotes' => ['tool "{{file}}"'];

        yield 'single quotes' => ["tool '{{file}}'"];
    }

    #[DataProvider('fileNames')]
    public function testAValueIsAlwaysASingleArgument(string $value): void
    {
        $this->assertSame(['tool', '-i', $value], CommandTemplate::toArgv('tool -i {{input}}', ['{{input}}' => $value]));
    }

    public static function fileNames(): iterable
    {
        foreach (['/a b.mp4', '/a;b.mp4', '/a|b.mp4', '/a&b.mp4', '/a>b.mp4', '/a$(b).mp4', '/a`b`.mp4', '/a"b.mp4', "/a'b.mp4", '/a\b.mp4', "/a\nb.mp4", '/é ñ.mp4'] as $value) {
            yield $value => [$value];
        }

        yield 'empty value' => [''];
    }

    public function testComposesSeveralVariablesInAnArgument(): void
    {
        $this->assertSame(['tool', '-s', '768x432'], CommandTemplate::toArgv('tool -s {{width}}x{{height}}', ['{{width}}' => 768, '{{height}}' => 432]));
    }

    public function testAVariableInsideAValueIsNotReplaced(): void
    {
        $this->assertSame(['tool', '/{{output}}.mp4', '/b.mp4'], CommandTemplate::toArgv('tool {{input}} {{output}}', ['{{input}}' => '/{{output}}.mp4', '{{output}}' => '/b.mp4']));
    }

    public function testKeepsQuotedTextInOneArgument(): void
    {
        $this->assertSame(['tool', '-vf', 'scale=1:2;crop=3', '--name=a b', ''], CommandTemplate::toArgv('tool -vf "scale=1:2;crop=3" --name="a b" ""'));
    }

    #[DataProvider('shellSyntax')]
    public function testRejectsTheSyntaxThatNeededAShell(string $template): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CommandTemplate::toArgv($template);
    }

    public static function shellSyntax(): iterable
    {
        foreach (['tool | other', 'tool > out', 'tool < in', 'tool 2>&1', 'tool && other', 'tool; other', 'tool $VAR', 'tool `other`', 'tool $(other)', 'tool *.mp4', 'tool ?.mp4', 'tool ~/a', 'tool # note', 'tool \ a', 'tool "$VAR"', 'tool "`other`"', 'tool "a\b"'] as $template) {
            yield $template => [$template];
        }
    }

    public function testSingleQuotesKeepEverythingLiteral(): void
    {
        $this->assertSame(['tool', '$VAR|a'], CommandTemplate::toArgv("tool '\$VAR|a'"));
    }

    #[DataProvider('malformedTemplates')]
    public function testRejectsMalformedTemplates(string $template): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CommandTemplate::toArgv($template);
    }

    public static function malformedTemplates(): iterable
    {
        yield 'empty' => [''];

        yield 'blank' => ['   '];

        yield 'unclosed quote' => ['tool "{{file}}'];
    }
}
