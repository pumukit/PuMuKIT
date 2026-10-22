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
    ];

    private const REFERENCE_PATTERN = '#(?<![\w./-])/?(bundles/([a-z0-9_]+)/[^\'"\s?\#{}()]*)#';

    private KernelInterface $kernel;
    private string $projectDir;

    public function __construct(KernelInterface $kernel, #[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->kernel = $kernel;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:check:assets')
            ->setDescription('List references in Twig templates to bundle assets that do not exist')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Directory with the templates to check (default: the project templates directory)', null)
            ->setHelp(
                <<<'EOT'
The <info>pumukit:check:assets</info> command looks for references to bundle public assets
(<comment>bundles/<bundle>/...</comment>) in the Twig templates of a directory and reports the ones
whose file is not provided by any installed bundle. When the asset was moved, it shows the
path that replaces it.

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
            foreach (file($file->getPathname()) as $index => $line) {
                if (!preg_match_all(self::REFERENCE_PATTERN, $line, $matches, PREG_SET_ORDER)) {
                    continue;
                }

                foreach ($matches as [, $reference, $bundle]) {
                    if ($this->exists($publicDirs, $bundle, $reference)) {
                        continue;
                    }

                    ++$missing;
                    $location = $file->getRelativePathname().':'.($index + 1);
                    $output->writeln('Missing asset: <info>'.$reference.'</info> in '.$location);
                    if (isset(self::REPLACEMENTS[$reference])) {
                        $output->writeln('    use <comment>'.self::REPLACEMENTS[$reference].'</comment>');
                    }
                }
            }
        }

        $output->writeln('<comment>***** Missing assets found: '.$missing.' *****</comment>');

        return $missing > 0 ? Command::FAILURE : Command::SUCCESS;
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
