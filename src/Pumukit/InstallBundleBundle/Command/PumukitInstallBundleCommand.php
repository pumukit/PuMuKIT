<?php

namespace Pumukit\InstallBundleBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;

class PumukitInstallBundleCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:install:bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle class with namespace')
            ->setDescription('Update Kernel (app/AppKernel.php) and routing (app/config/routing.yml) to enable the bundle.')
            ->setHelp(<<<EOT
The <info>pumukit:install:bundle</info> command helps you installs bundles.

The command updates the Kernel to enable the bundle (<comment>app/AppKernel.php</comment>) and loads the routing (<comment>app/config/routing.yml</comment>) to add the bundle routes.

<info>php app/console pumukit:install:bundle Pumukit/Cmar/WebTVBundle/PumukitCmarWebTVBundle</info>

Note that the bundle namespace must end with "Bundle" and  / instead of \\ has to be used for the namespace delimiter to avoid any problem.
EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleName = $input->getArgument('bundle');
        $bundle = str_replace('/', '\\', $bundleName);

        if (!class_exists($bundle)) {
            throw new \RuntimeException(sprintf('Class "%s" doesn\'t exist.', $bundle));
        }

        $refClass = new \ReflectionClass($bundle);
        if (!$refClass->isSubclassOf('Symfony\Component\HttpKernel\Bundle\Bundle')) {
            throw new \RuntimeException(sprintf('Class "%s" doesn\'t extend of "Symfony\Component\HttpKernel\Bundle\Bundle".', $bundle));
        }

        $kernel = $this->getContainer()->get('kernel');
        $this->updateKernel($input, $output, $kernel, $bundle);
        $this->updateRouting($input, $output, $bundle);
    }

  
    protected function updateKernel(InputInterface $input, OutputInterface $output, KernelInterface $kernel, $bundle)
    {
        $manip = new KernelManipulator($kernel);

        try {
            $ret = $manip->addBundle($bundle);

            if (!$ret) {
                $reflected = new \ReflectionObject($kernel);

                $output->writeln(sprintf('- Edit <comment>%s</comment>', $reflected->getFilename()));
                $output->writeln("  and add the following bundle in the <comment>AppKernel::registerBundles()</comment> method:\n");
                $output->writeln(sprintf("    <comment>new %s(),</comment>\n", $bundle));
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('Bundle <comment>%s</comment> is already defined in <comment>AppKernel::registerBundles()</comment>.', $bundle));
            throw $e;
        }
    }


    

    protected function updateRouting(InputInterface $input, OutputInterface $output, $bundle, $format="yml")
    {
      $routing = new RoutingManipulator($this->getContainer()->getParameter('kernel.root_dir').'/config/routing.yml');
      $bundleName = substr($bundle, 1+ strrpos($bundle, "\\"));;
      try {
        $ret = $routing->addResource($bundleName, $format);
        if (!$ret) {
          if ('annotation' === $format) {
            $help = sprintf("        <comment>resource: \"@%s/Controller/\"</comment>\n        <comment>type:     annotation</comment>\n", $bundle);
          } else {
            $help = sprintf("        <comment>resource: \"@%s/Resources/config/routing.%s\"</comment>\n", $bundle, $format);
          }
          $help .= "        <comment>prefix:   /</comment>\n";

          $output->writeln("- Import the bundle\'s routing resource in the app main routing file:\n");
          $output->writeln(sprintf("    <comment>%s:</comment>\n", $bundle));
          $output->writeln($help);
        }
      } catch (\RuntimeException $e) {
          $output->writeln(sprintf("Bundle <comment>%s</comment> is already imported.", $bundle));
          throw $e;
      }

    }
}
