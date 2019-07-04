<?php

namespace Pumukit\InstallBundleBundle\Manipulator;

use Sensio\Bundle\GeneratorBundle\Manipulator\Manipulator;
use Symfony\Component\DependencyInjection\Container;

/**
 * Changes the PHP code of a YAML routing file.
 */
class RoutingManipulator extends Manipulator
{
    private $file;

    /**
     * Constructor.
     *
     * @param string $file The YAML routing file path
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Adds a routing resource at the top of the existing ones.
     *
     * @param string $bundle
     * @param string $format
     * @param string $prefix
     * @param string $path
     * @param bool   $appendToEnd
     * @param mixed  $type
     *
     * @throws \RuntimeException If bundle is already imported
     *
     * @return bool true if it worked, false otherwise
     */
    public function addResource($bundle, $format, $prefix = '/', $type = '', $path = 'routing', $appendToEnd = false)
    {
        $current = '';
        $code = sprintf("%s:\n", Container::underscore(substr($bundle, 0, -6)).('/' !== $prefix ? '_'.str_replace('/', '_', substr($prefix, 1)) : ''));
        if (file_exists($this->file)) {
            $current = file_get_contents($this->file);

            // Don't add same bundle twice
            if (false !== strpos($current, $code)) {
                throw new \RuntimeException(sprintf('Bundle "%s" is already imported.', $bundle));
            }
        } elseif (!is_dir($dir = dirname($this->file))) {
            mkdir($dir, 0777, true);
        }

        if ('annotation' == $format) {
            $code .= sprintf("    resource: \"@%s/Controller/\"\n    type:     annotation\n", $bundle);
        } else {
            $code .= sprintf("    resource: \"@%s/Resources/config/%s.%s\"\n", $bundle, $path, $format);
        }
        $code .= sprintf("    prefix:   %s\n", $prefix);

        if ('' !== $type) {
            $code .= sprintf("    type:   %s\n", $type);
        }

        $code .= "\n";

        if ($appendToEnd) {
            $code = $current."\n".$code;
        } else {
            $code .= $current;
        }

        if (false === file_put_contents($this->file, $code)) {
            return false;
        }

        return true;
    }
}
