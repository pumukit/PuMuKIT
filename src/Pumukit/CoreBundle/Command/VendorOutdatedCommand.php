<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VendorOutdatedCommand extends Command
{
    public const PACKAGES = [
        'jquery' => ['file' => 'jquery/jquery.min.js', 'pattern' => '/jQuery v(\d+\.\d+\.\d+)/'],
        'bootstrap' => ['file' => 'bootstrap5/js/bootstrap.bundle.min.js', 'pattern' => '/Bootstrap v(\d+\.\d+\.\d+)/'],
        '@fortawesome/fontawesome-free' => ['file' => 'fontawesome/css/all.min.css', 'pattern' => '/Font Awesome Free (\d+\.\d+\.\d+)/'],
    ];

    private string $vendorDir;

    public function __construct(private readonly HttpClientInterface $httpClient, ?string $vendorDir = null)
    {
        $this->vendorDir = $vendorDir ?? \dirname(__DIR__).'/Resources/public/vendor';
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:vendor:outdated')
            ->setDescription('List the updates of the libraries copied to bundles/pumukitcore/vendor (jQuery, Bootstrap and Font Awesome)')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:vendor:outdated</info> command reads the version of jQuery, Bootstrap and
Font Awesome from the header of their files in <comment>CoreBundle/Resources/public/vendor</comment> and compares it
with the latest version published in npm, within the same major version and overall.

These libraries are loaded as classic scripts and stylesheets, outside the importmap, so they
are updated by hand: download the new files and replace the old ones.

    Example:
        php bin/console pumukit:vendor:outdated
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $responses = [];
        foreach (array_keys(self::PACKAGES) as $package) {
            $responses[$package] = $this->httpClient->request('GET', 'https://registry.npmjs.org/'.str_replace('/', '%2F', $package), ['headers' => ['Accept' => 'application/vnd.npm.install-v1+json']]);
        }

        $rows = [];
        foreach (self::PACKAGES as $package => ['file' => $file, 'pattern' => $pattern]) {
            $current = $this->currentVersion($this->vendorDir.'/'.$file, $pattern);
            $metadata = $responses[$package]->toArray();
            $latest = $metadata['dist-tags']['latest'];
            $latestInMajor = null === $current ? null : $this->latestInMajor(array_keys($metadata['versions']), $current);

            $rows[] = [$package, $current ?? 'unknown', $latestInMajor ?? '-', $latest, $this->status($current, $latestInMajor, $latest)];
        }

        $table = new Table($output);
        $table->setHeaders(['Library', 'Current', 'Latest in its major', 'Latest', '']);
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }

    private function currentVersion(string $file, string $pattern): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        return preg_match($pattern, (string) file_get_contents($file, false, null, 0, 1024), $matches) ? $matches[1] : null;
    }

    private function latestInMajor(array $versions, string $current): ?string
    {
        $major = explode('.', $current)[0];
        $candidates = array_filter($versions, static fn (string $version) => 1 === preg_match('/^\d+\.\d+\.\d+$/', $version) && explode('.', $version)[0] === $major);
        usort($candidates, 'version_compare');

        return $candidates ? end($candidates) : null;
    }

    private function status(?string $current, ?string $latestInMajor, string $latest): string
    {
        if (null === $current) {
            return 'version not found in the file';
        }
        if (null !== $latestInMajor && version_compare($latestInMajor, $current, '>')) {
            return 'update available';
        }
        if (version_compare($latest, $current, '>')) {
            return 'new major version';
        }

        return 'up to date';
    }
}
