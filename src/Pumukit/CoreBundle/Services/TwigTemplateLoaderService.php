<?php

namespace Pumukit\CoreBundle\Services;

use Symfony\Bundle\TwigBundle\Loader\FilesystemLoader as BaseFilesystemLoader;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;

class TwigTemplateLoaderService extends BaseFilesystemLoader
{
    public $currentNamespace;

    private $baseNamespace;

    public function __construct(FileLocatorInterface $locator, TemplateNameParserInterface $parser, $rootPath = null, $baseNamespace = null, $currentNamespace = null)
    {
        $this->baseNamespace = $baseNamespace;
        $this->currentNamespace = $currentNamespace;

        parent::__construct($locator, $parser, $rootPath);
    }

    public function exists($name)
    {
        if (!$this->currentNamespace || 0 !== strpos($name, $this->baseNamespace.':')) {
            return false;
        }

        return parent::exists($this->upgradeNamespace($name));
    }

    protected function findTemplate($template, $throw = true)
    {
        return parent::findTemplate($this->upgradeNamespace($template), $throw);
    }

    private function upgradeNamespace($name)
    {
        return str_replace($this->baseNamespace.':', $this->currentNamespace.':', $name);
    }
}
