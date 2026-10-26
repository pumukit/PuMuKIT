<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversNothing]
class CheckAssetsCommandTest extends PumukitTestCase
{
    private string $tmpDir;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/pumukit-assets-'.uniqid('', true);
        mkdir($this->tmpDir);
    }

    public function tearDown(): void
    {
        if (isset($this->tmpDir) && is_dir($this->tmpDir)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    public function testReportsRemovedAssetWithItsReplacement(): void
    {
        file_put_contents(
            $this->tmpDir.'/layout.html.twig',
            "<link href=\"{{ asset('bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css') }}\">\n<script src=\"{{ asset('bundles/pumukitwebtv/js/jquery.min.js') }}\"></script>\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Missing asset: bundles/pumukitwebtv/js/jquery.min.js in layout.html.twig:2', $display);
        $this->assertStringContainsString('use bundles/pumukitcore/vendor/jquery/jquery.min.js', $display);
        $this->assertStringNotContainsString('bootstrap.min.css', $display);
    }

    public function testReportsAssetOfBundleThatIsNotInstalled(): void
    {
        file_put_contents($this->tmpDir.'/chat.html.twig', "<link href=\"/bundles/pumukitmissing/css/chat.css\">\n");

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Missing asset: bundles/pumukitmissing/css/chat.css in chat.html.twig:1', $tester->getDisplay());
    }

    public function testSucceedsWhenEveryAssetExists(): void
    {
        file_put_contents(
            $this->tmpDir.'/template.html.twig',
            "<script src=\"{{ asset('bundles/pumukitcore/vendor/jquery/jquery.min.js') }}\"></script>\n<img src=\"{{ asset('bundles/pumukitwebtv/images/categories/' ~ name) }}\">\n<base href=\"../bundles/pumukitmissing/\">\n{% extends ['@PumukitWebTV/Layout/template.html.twig', '@PumukitWebTV/Layout/missing.html.twig'] %}\n{% include '@PumukitWebTV/missing.html.twig' ignore missing %}\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Problems found: 0', $tester->getDisplay());
    }

    public function testReportsRemovedTemplateWithItsReplacement(): void
    {
        file_put_contents(
            $this->tmpDir.'/template.html.twig',
            "{% include '@PumukitCore/Upload/uppy_drag_and_drop.html.twig' %}\n<script>{% include \"@PumukitCore/Upload/uppy_tus.js.twig\" %}</script>\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Missing template: @PumukitCore/Upload/uppy_tus.js.twig in template.html.twig:2', $display);
        $this->assertStringContainsString('use @PumukitCore/Upload/uppy_tus.html.twig', $display);
        $this->assertStringNotContainsString('uppy_drag_and_drop', $display);
    }

    public function testReportsPortalLayoutWithoutImportmap(): void
    {
        mkdir($this->tmpDir.'/bundles/PumukitWebTVBundle/Layout', 0o777, true);
        file_put_contents($this->tmpDir.'/bundles/PumukitWebTVBundle/Layout/template.html.twig', "<html><head>{% block javascripts %}{% endblock %}</head></html>\n");
        file_put_contents($this->tmpDir.'/base.html.twig', "<html><head></head></html>\n");
        file_put_contents($this->tmpDir.'/bundles/PumukitWebTVBundle/Layout/menu.html.twig', "{% include '@PumukitWebTV/Layout/template.html.twig' %}\n");

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString("Missing {{ importmap('pumukit') }} in the javascripts block of bundles/PumukitWebTVBundle/Layout/template.html.twig", $display);
        $this->assertStringContainsString('Missing bundles/pumukitcore/css/pumukit-utilities.css stylesheet in bundles/PumukitWebTVBundle/Layout/template.html.twig', $display);
        $this->assertStringContainsString('Problems found: 2', $display);
    }

    public function testReportsRemovedJavascriptGlobalsThatTheProjectDoesNotDefine(): void
    {
        file_put_contents(
            $this->tmpDir.'/player.html.twig',
            "<iframe onload=\"resizeFrame(this)\"></iframe>\n<select onchange=\"submitForm(this);\"></select>\n"
        );
        file_put_contents($this->tmpDir.'/template_player.html.twig', "<script>function resizeFrame(elem) {}</script>\n");

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Removed JavaScript global: submitForm in player.html.twig:2', $display);
        $this->assertStringContainsString('use the webtv--search controller', $display);
        $this->assertStringNotContainsString('resizeFrame', $display);
        $this->assertStringContainsString('Problems found: 1', $display);
    }

    public function testReportsRemovedGlobalsCalledThroughWindow(): void
    {
        file_put_contents(
            $this->tmpDir.'/list.html.twig',
            "<a onclick=\"window.submit_delete_role('1', '0'); return false;\"></a>\n<a onclick=\"other.click_fila_url()\"></a>\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Removed JavaScript global: submit_delete_role in list.html.twig:1', $display);
        $this->assertStringContainsString('admin--actions#remove', $display);
        $this->assertStringNotContainsString('click_fila_url', $display);
        $this->assertStringContainsString('Problems found: 1', $display);
    }

    public function testReportsRemovedJavascriptHooksThatTheProjectStillDefines(): void
    {
        file_put_contents(
            $this->tmpDir.'/index.html.twig',
            "<script>\nwindow.update_list = function(seriesId) {};\nif (window.update_list == null) {}\n</script>\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Removed JavaScript hook: update_list in index.html.twig:2', $display);
        $this->assertStringContainsString('use a data-refresh-url attribute on #list_mms', $display);
        $this->assertStringContainsString('Problems found: 1', $display);
    }

    public function testReportsUnsupportedTwigFunctions(): void
    {
        file_put_contents(
            $this->tmpDir.'/index.html.twig',
            "<div id=\"preview_mm\">\n    {{ render_hinclude(url('pumukitnewadmin_mms_show', {'id': id})) }}\n</div>\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Unsupported Twig function: render_hinclude in index.html.twig:2', $display);
        $this->assertStringContainsString('data-controller="admin--include"', $display);
        $this->assertStringContainsString('Problems found: 1', $display);
    }

    public function testReportsJqueryUtilitiesRemovedInJquery4(): void
    {
        file_put_contents(
            $this->tmpDir.'/index.html.twig',
            "<script>\nvar name = $.trim(value);\nif (jQuery.isArray(list)) {}\nvar text = value.trim();\n$('#a').css('top', 0);\n</script>\n"
        );

        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir]);

        $display = $tester->getDisplay();
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Removed jQuery API: $.trim in index.html.twig:2', $display);
        $this->assertStringContainsString('use String.prototype.trim()', $display);
        $this->assertStringContainsString('Removed jQuery API: $.isArray in index.html.twig:3', $display);
        $this->assertStringContainsString('Problems found: 2', $display);
    }

    public function testFailsWhenPathDoesNotExist(): void
    {
        $tester = $this->commandTester();
        $tester->execute(['--path' => $this->tmpDir.'/missing']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:check:assets');

        return new CommandTester($command);
    }
}
