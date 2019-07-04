<?php

namespace Pumukit\InstallBundleBundle\Command;

use Pumukit\InstallBundleBundle\Manipulator\RoutingManipulator;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;

class PumukitInstallBundleCommand extends ContainerAwareCommand
{
    private $uninstall;
    private $reflected;

    /**
     * Removes an existing bundle from the appKernel register bundles.
     *
     * Quite a naive approach, if finds the line (or lines) with the bundle and removes it (them) from the file
     *
     * @param string $bundle The bundle class name
     *
     * @throws \RuntimeException If bundle is not defined
     *
     * @return bool true if it worked, false otherwise
     */
    public function removeBundle($bundle)
    {
        $kernel = $this->getContainer()->get('kernel');
        $this->reflected = new \ReflectionObject($kernel);

        if (!$this->reflected->getFilename()) {
            return false;
        }

        $src = file($this->reflected->getFilename());

        $method = $this->reflected->getMethod('registerBundles');
        $lines = array_slice($src, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);

        // Exception if the bundle is not there anyways
        if (false === strpos(implode('', $lines), $bundle)) {
            throw new \RuntimeException(sprintf('Bundle "%s" is already not defined in "AppKernel::registerBundles()".', $bundle));
        }

        //Finds the bundle inside 'registerBundles' function and removes it.
        foreach ($lines as $key => $line) {
            if (false !== strpos($line, $bundle)) {
                $srcKey = $key + $method->getStartLine() - 1;
                unset($src[$srcKey]);
            }
        }

        file_put_contents($this->reflected->getFilename(), implode('', $src));

        return true;
    }

    /**
     * Removes a routing resource.
     *
     * @param string $bundle
     * @param string $format
     * @param string $prefix
     * @param string $path
     *
     * @throws \RuntimeException If bundle is not found on file
     *
     * @return bool true if it worked, false otherwise
     */
    public function removeResource($bundle, $format, $prefix = '/', $path = 'routing')
    {
        $current = '';
        $routingFile = $this->getContainer()->getParameter('kernel.root_dir').'/config/routing.yml';

        $code = sprintf("%s:\n", Container::underscore(substr($bundle, 0, -6)).('/' !== $prefix ? '_'.str_replace('/', '_', substr($prefix, 1)) : ''));

        if (file_exists($routingFile)) {
            $current = file_get_contents($routingFile);

            // Exception in case the bundle does not exist
            if (false === strpos($current, $code)) {
                throw new \RuntimeException(sprintf('Bundle "%s" is already not imported.', $bundle));
            }
        } else {
            throw new \RuntimeException(sprintf('The routing file %s does not exist', $routingFile));
        }

        $src = file($routingFile);
        $numSpaces = 0;
        foreach ($src as $key => $line) {
            if (false !== strpos($line, $code)) {
                $numSpaces = preg_match('/^( *)'.$code.'/', $line, $results);
                $numSpaces = count((array) $results[1]);
                unset($src[$key]);

                continue;
            }
            if (0 != $numSpaces &&
                (0 == strlen(trim($line)) ||
                1 === preg_match('/^( ){'.$numSpaces.'}.*/', $line))) {
                unset($src[$key]);
            } else {
                $numSpaces = 0;
            }
        }

        if (false === file_put_contents($routingFile, implode('', $src))) {
            return false;
        }

        return true;
    }

    protected function configure()
    {
        $this
            ->setName('pumukit:install:bundle')
            ->addArgument('bundle', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'List of bundles classes with namespace')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Optionally specify a \'prefix\' for the routing config(default: \'/\')', '/')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Optionally specify a \'type\' for the routing config (default: \'\')', '')
            ->addOption('append-to-end', null, InputOption::VALUE_NONE, 'Set this parameter to append the routing bundle configuration to the end of routing file')
            ->addOption('uninstall', null, InputOption::VALUE_NONE)
            ->setDescription('Update Kernel (app/AppKernel.php) and routing (app/config/routing.yml) to enable the bundle.')
            ->setHelp(
                <<<'EOT'
The <info>pumukit:install:bundle</info> command helps you installs bundles.

The command updates the Kernel to enable the bundle (<comment>app/AppKernel.php</comment>) and loads the routing (<comment>app/config/routing.yml</comment>) to add the bundle routes.

The parameter --append-to-end adds the bundle routes at the end fo the <comment>app/config/routing.yml</comment> file.

<info>php app/console pumukit:install:bundle Pumukit/Cmar/WebTVBundle/PumukitCmarWebTVBundle</info>

Note that the bundle namespace must end with "Bundle" and  / instead of \ has to be used for the namespace delimiter to avoid any problem.
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getContainer()->get('kernel');
        foreach ($input->getArgument('bundle') as $bundleName) {
            $bundle = $this->prepareBundleName($bundleName);

            if (!class_exists($bundle)) {
                throw new \RuntimeException(sprintf('Class "%s" doesn\'t exist.', $bundle));
            }

            $refClass = new \ReflectionClass($bundle);
            if (!$refClass->isSubclassOf('Symfony\Component\HttpKernel\Bundle\Bundle')) {
                throw new \RuntimeException(sprintf('Class "%s" doesn\'t extend of "Symfony\Component\HttpKernel\Bundle\Bundle".', $bundle));
            }
        }

        $prefix = $input->getOption('prefix');
        $type = $input->getOption('type');
        $appendToEnd = $input->getOption('append-to-end');
        $this->uninstall = $input->getOption('uninstall');

        foreach ($input->getArgument('bundle') as $bundleName) {
            $bundle = $this->prepareBundleName($bundleName);
            $this->updateKernel($input, $output, $kernel, $bundle);
            $this->updateRouting($input, $output, $bundle, 'yml', $prefix, $type, $appendToEnd);
        }
    }

    protected function updateKernel(InputInterface $input, OutputInterface $output, KernelInterface $kernel, $bundle)
    {
        $manip = new KernelManipulator($kernel);

        try {
            if (!$this->uninstall) {
                $ret = $manip->addBundle($bundle);
            } else {
                $ret = $this->removeBundle($bundle);
                //$ret = $manip->removeBundle($bundle);//DOESNT EXIST
            }

            if (!$ret) {
                $reflected = new \ReflectionObject($kernel);

                $output->writeln(sprintf('- Edit <comment>%s</comment>', $reflected->getFilename()));
                $output->writeln("  and add the following bundle in the <comment>AppKernel::registerBundles()</comment> method:\n");
                $output->writeln(sprintf("    <comment>new %s(),</comment>\n", $bundle));
            }
        } catch (\RuntimeException $e) {
            if (!$this->uninstall) {
                $output->writeln(sprintf('Bundle <comment>%s</comment> is already defined in <comment>AppKernel::registerBundles()</comment>.', $bundle));
            } else {
                $output->writeln(sprintf('Bundle <comment>%s</comment> is already not defined in <comment>AppKernel::registerBundles()</comment>.', $bundle));
            }
        }
    }

    protected function updateRouting(InputInterface $input, OutputInterface $output, $bundle, $format = 'yml', $prefix = '/', $type = '', $appendToEnd = false)
    {
        $refClass = new \ReflectionClass($bundle);
        $bundleRoutingFile = sprintf('%s/Resources/config/routing.%s', dirname($refClass->getFileName()), $format);
        if (is_file($bundleRoutingFile)) {
            $routing = new RoutingManipulator($this->getContainer()->getParameter('kernel.root_dir').'/config/routing.yml');
            $bundleName = substr($bundle, 1 + strrpos($bundle, '\\'));

            try {
                if (!$this->uninstall) {
                    $ret = $routing->addResource($bundleName, $format, $prefix, $type, 'routing', $appendToEnd);
                } else {
                    $ret = $this->removeResource($bundleName, $format, $prefix, 'routing');
                }

                if (!$ret) {
                    if ('annotation' === $format) {
                        $help = sprintf("        <comment>resource: \"@%s/Controller/\"</comment>\n        <comment>type:     annotation</comment>\n", $bundle);
                    } else {
                        $help = sprintf("        <comment>resource: \"@%s/Resources/config/routing.%s\"</comment>\n", $bundle, $format);
                    }
                    $help .= "        <comment>prefix:   /</comment>\n";

                    $output->writeln("- Import the bundle\\'s routing resource in the app main routing file:\n");
                    $output->writeln(sprintf("    <comment>%s:</comment>\n", $bundle));
                    $output->writeln($help);
                }
            } catch (\RuntimeException $e) {
                if (!$this->uninstall) {
                    $output->writeln(sprintf('Bundle <comment>%s</comment> is already imported.', $bundle));
                } else {
                    $output->writeln(sprintf('Bundle <comment>%s</comment> is already not imported.', $bundle));
                }
            }
        } else {
            $output->writeln(sprintf('<comment>Warning: </comment> The routing file %s for the %s bundle does not exist', $bundleRoutingFile, $bundle));
        }
    }

    private function prepareBundleName($bundleName)
    {
        //Helper to autocomplete in a shell: delete "src/" from the begin and ".php" from the tail.
        $bundleName = preg_replace('/^src\/(.*?)\.php$/i', '${1}', $bundleName);

        return str_replace('/', '\\', $bundleName);
    }
}
