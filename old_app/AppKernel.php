<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new SunCat\MobileDetectBundle\MobileDetectBundle(),
            new Pumukit\CoreBundle\PumukitCoreBundle(),
            new Pumukit\SchemaBundle\PumukitSchemaBundle(),
            new Pumukit\EncoderBundle\PumukitEncoderBundle(),
            new Pumukit\InspectionBundle\PumukitInspectionBundle(),
            new Pumukit\NewAdminBundle\PumukitNewAdminBundle(),
            new Pumukit\NotificationBundle\PumukitNotificationBundle(),
            new Pumukit\BaseLivePlayerBundle\PumukitBaseLivePlayerBundle(),
            new Pumukit\WorkflowBundle\PumukitWorkflowBundle(),
            new Pumukit\WizardBundle\PumukitWizardBundle(),
            new Pumukit\WebTVBundle\PumukitWebTVBundle(),
            new Pumukit\StatsBundle\PumukitStatsBundle(),
            new Pumukit\BasePlayerBundle\PumukitBasePlayerBundle(),
            new Pumukit\JWPlayerBundle\PumukitJWPlayerBundle(),
            new Pumukit\StatsUIBundle\PumukitStatsUIBundle(),
            new Vipx\BotDetectBundle\VipxBotDetectBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
