<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class CheckAssetsCommand extends Command
{
    public const REPLACEMENTS = [
        'bundles/pumukitnewadmin/js/jquery-1.10.2.min.js' => 'bundles/pumukitcore/vendor/jquery/jquery.min.js',
        'bundles/pumukitnewadmin/js/bootstrap.min.js' => 'bundles/pumukitcore/vendor/bootstrap/js/bootstrap.min.js',
        'bundles/pumukitnewadmin/css/bootstrap.min.css' => 'bundles/pumukitcore/vendor/bootstrap/css/bootstrap.min.css',
        'bundles/pumukitwebtv/js/jquery.min.js' => 'bundles/pumukitcore/vendor/jquery/jquery.min.js',
        'bundles/pumukitwebtv/js/bootstrap.min.js' => 'bundles/pumukitcore/vendor/bootstrap/js/bootstrap.min.js',
        'bundles/pumukitwebtv/js/moment-with-locales.min.js' => 'bundles/pumukitcore/vendor/moment/moment-with-locales.min.js',
        'bundles/pumukitstatsui/vendor/moment/moment.min.js' => 'bundles/pumukitcore/vendor/moment/moment-with-locales.min.js',
        'bundles/pumukitlive/css/chat.css' => 'bundles/pumukitwebtv/css/live/chat.css',
        'bundles/pumukitnewadmin/js/jquery-ui-1.11.3.custom.min.js' => 'bundles/pumukitcore/vendor/jquery-ui/jquery-ui.min.js',
        'bundles/pumukitnewadmin/css/jquery-ui-1.11.3.custom.min.css' => 'bundles/pumukitcore/vendor/jquery-ui/jquery-ui.min.css',
        'bundles/pumukitnewadmin/css/jquery-ui-1.11.3.custom.structure.min.css' => 'bundles/pumukitcore/vendor/jquery-ui/jquery-ui.min.css',
        'bundles/pumukitnewadmin/css/jquery-ui-1.11.3.custom.theme.min.css' => 'bundles/pumukitcore/vendor/jquery-ui/jquery-ui.min.css',
        'bundles/pumukitwebtv/js/bootstrap-datetimepicker.min.js' => 'bundles/pumukitcore/vendor/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js',
        'bundles/pumukitwebtv/css/bootstrap-datetimepicker.min.css' => 'bundles/pumukitcore/vendor/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css',
        'bundles/pumukitwebtv/css/bootstrap-datetimepicker.css' => 'bundles/pumukitcore/vendor/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css',
        '@PumukitCore/Upload/uppy_tus.js.twig' => '@PumukitCore/Upload/uppy_tus.html.twig (include it outside the <script> tag)',
        '@PumukitCore/Upload/uppy_dispatch.js.twig' => '@PumukitCore/Upload/uppy_tus.html.twig (include it outside the <script> tag)',
    ];

    private const REFERENCE_PATTERN = '#(?<![\w./-])/?(bundles/([a-z0-9_]+)/[^\'"\s?\#{}()]*)#';
    private const TEMPLATE_PATTERN = '#[\'"](@Pumukit[A-Za-z]+/[^\'"]+\.twig)[\'"]#';
    private const PORTAL_LAYOUT = 'PumukitWebTVBundle/Layout/template.html.twig';
    private const OPTIONAL_TEMPLATE_PATTERN = '#\{%-?\s*(extends|include|embed)\s*\[|ignore missing#';

    private KernelInterface $kernel;
    private Environment $twig;
    private string $projectDir;

    public function __construct(KernelInterface $kernel, Environment $twig, #[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->kernel = $kernel;
        $this->twig = $twig;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:check:assets')
            ->setDescription('List references in Twig templates to bundle assets and PuMuKIT templates that do not exist')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Directory with the templates to check (default: the project templates directory)', null)
            ->setHelp(
                <<<'EOT'
The <info>pumukit:check:assets</info> command looks for references to bundle public assets
(<comment>bundles/<bundle>/...</comment>) and to PuMuKIT templates (<comment>@PumukitXxx/...twig</comment>)
in the Twig templates of a directory and reports the ones that no installed bundle provides.
When the asset or template was moved, it shows the one that replaces it. It also reports an
overridden portal layout (<comment>PumukitWebTVBundle/Layout/template.html.twig</comment>) that does not
load <comment>importmap('pumukit')</comment>.

Run it on the overridden templates of a project before upgrading PuMuKIT. It exits with an
error code when a missing asset is found.

    Example:
        php bin/console pumukit:check:assets
        php bin/console pumukit:check:assets --path=templates/bundles/PumukitWebTVBundle
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path') ?? $this->projectDir.'/templates';
        if (!is_dir($path)) {
            $output->writeln('<error>Path does not exist: '.$path.'</error>');

            return Command::FAILURE;
        }

        $publicDirs = $this->bundlePublicDirs();
        $missing = 0;

        $finder = (new Finder())->files()->in($path)->name('*.twig')->sortByName();
        foreach ($finder as $file) {
            $lines = file($file->getPathname());
            foreach ($lines as $index => $line) {
                $location = $file->getRelativePathname().':'.($index + 1);

                preg_match_all(self::REFERENCE_PATTERN, $line, $matches, PREG_SET_ORDER);
                foreach ($matches as [, $reference, $bundle]) {
                    if (!$this->exists($publicDirs, $bundle, $reference)) {
                        ++$missing;
                        $this->report($output, 'Missing asset', $reference, $location);
                    }
                }

                if (preg_match(self::OPTIONAL_TEMPLATE_PATTERN, $line)) {
                    continue;
                }

                preg_match_all(self::TEMPLATE_PATTERN, $line, $matches, PREG_SET_ORDER);
                foreach ($matches as [, $template]) {
                    if (!$this->templateExists($path, $template)) {
                        ++$missing;
                        $this->report($output, 'Missing template', $template, $location);
                    }
                }
            }

            if (str_ends_with($file->getRelativePathname(), self::PORTAL_LAYOUT) && !str_contains(implode('', $lines), 'importmap(')) {
                ++$missing;
                $output->writeln('Missing <info>{{ importmap(\'pumukit\') }}</info> in the javascripts block of '.$file->getRelativePathname());
            }
        }

        $output->writeln('<comment>***** Problems found: '.$missing.' *****</comment>');

        return $missing > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function templateExists(string $path, string $template): bool
    {
        if ($this->twig->getLoader()->exists($template)) {
            return true;
        }

        [$namespace, $relativePath] = explode('/', substr($template, 1), 2);

        return file_exists($path.'/bundles/'.$namespace.'Bundle/'.$relativePath);
    }

    private function report(OutputInterface $output, string $problem, string $reference, string $location): void
    {
        $output->writeln($problem.': <info>'.$reference.'</info> in '.$location);
        if (isset(self::REPLACEMENTS[$reference])) {
            $output->writeln('    use <comment>'.self::REPLACEMENTS[$reference].'</comment>');
        }
    }

    private function bundlePublicDirs(): array
    {
        $dirs = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $name = preg_replace('/bundle$/', '', strtolower($bundle->getName()));
            foreach ([$bundle->getPath().'/Resources/public', $bundle->getPath().'/public'] as $dir) {
                if (is_dir($dir)) {
                    $dirs[$name] = $dir;

                    break;
                }
            }
        }

        return $dirs;
    }

    private function exists(array $publicDirs, string $bundle, string $reference): bool
    {
        if (!isset($publicDirs[$bundle])) {
            return false;
        }

        $relativePath = substr($reference, strlen('bundles/'.$bundle));

        return file_exists($publicDirs[$bundle].$relativePath);
    }
}
