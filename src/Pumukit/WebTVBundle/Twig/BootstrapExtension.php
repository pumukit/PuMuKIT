<?php

namespace Pumukit\WebTVBundle\Twig;

use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BootstrapExtension extends AbstractExtension
{
    protected $context;

    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('col_calculator', [$this, 'getCols']),
            new TwigFunction('add_clear_fix_md', [$this, 'getClearFixMediumDevices']),
            new TwigFunction('add_clear_fix_sm', [$this, 'getClearFixSmallDevices']),
        ];
    }

    public function getCols($objectsByCol): string
    {
        $objectsByCol = (int) $objectsByCol;

        $mapping = [
            1 => 'col-xs-12 col-sm-12 col-md-12',
            2 => 'col-xs-12 col-sm-6 col-md-6',
            3 => 'col-xs-12 col-sm-12 col-md-4',
            4 => 'col-xs-12 col-sm-6 col-md-3',
            6 => 'col-xs-12 col-sm-6 col-md-2',
            12 => 'col-xs-12 col-sm-12 col-md-1',
        ];

        if (!array_key_exists($objectsByCol, $mapping)) {
            return $mapping[1];
        }

        return $mapping[$objectsByCol];
    }

    public function getClearFixMediumDevices(int $loopIndex, $objectsByCol): bool
    {
        $objectsByCol = (int) $objectsByCol;

        $mapping = [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            6 => 6,
            12 => 12,
        ];

        if (!array_key_exists($objectsByCol, $mapping)) {
            return false;
        }

        if (0 !== $loopIndex % $mapping[$objectsByCol]) {
            return false;
        }

        return true;
    }

    public function getClearFixSmallDevices(int $loopIndex, $objectsByCol): bool
    {
        $objectsByCol = (int) $objectsByCol;

        $mapping = [
            1 => 1,
            2 => 2,
            3 => 4,
            4 => 2,
            6 => 6,
            12 => 12,
        ];

        if (!array_key_exists($objectsByCol, $mapping)) {
            return false;
        }

        if (0 !== $loopIndex % $mapping[$objectsByCol]) {
            return false;
        }

        return true;
    }
}
