<?php

namespace Pumukit\WebTVBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class BootstrapExtension.
 */
class BootstrapExtension extends AbstractExtension
{
    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * BootstrapExtension constructor.
     *
     * @param DocumentManager $documentManager
     * @param RequestContext  $context
     */
    public function __construct(DocumentManager $documentManager, RequestContext $context)
    {
        $this->dm = $documentManager;
        $this->context = $context;
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('col_calculator', [$this, 'getCols']),
            new TwigFunction('add_clear_fix_md', [$this, 'getClearFixMediumDevices']),
            new TwigFunction('add_clear_fix_sm', [$this, 'getClearFixSmallDevices']),
        ];
    }

    /**
     * @param $objectsByCol
     *
     * @return mixed
     */
    public function getCols($objectsByCol)
    {
        $mapping = [
            '1' => 'col-xs-12 col-sm-12 col-md-12',
            '2' => 'col-xs-12 col-sm-6 col-md-6',
            '3' => 'col-xs-12 col-sm-12 col-md-4',
            '4' => 'col-xs-12 col-sm-6 col-md-3',
            '6' => 'col-xs-12 col-sm-6 col-md-2',
            '12' => 'col-xs-12 col-sm-12 col-md-1',
        ];

        if (!array_key_exists($objectsByCol, $mapping)) {
            return $mapping[1];
        }

        return $mapping[$objectsByCol];
    }

    /**
     * @param $loopIndex
     * @param $objectsByCol
     *
     * @return bool
     */
    public function getClearFixMediumDevices($loopIndex, $objectsByCol)
    {
        $mapping = [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '6' => 6,
            '12' => 12,
        ];

        if (!array_key_exists($objectsByCol, $mapping)) {
            return false;
        }

        if (0 != $loopIndex % $mapping[$objectsByCol]) {
            return false;
        }

        return true;
    }

    /**
     * @param $loopIndex
     * @param $objectsByCol
     *
     * @return bool
     */
    public function getClearFixSmallDevices($loopIndex, $objectsByCol)
    {
        $mapping = [
            '1' => 1,
            '2' => 2,
            '3' => 4,
            '4' => 2,
            '6' => 6,
            '12' => 12,
        ];

        if (!array_key_exists($objectsByCol, $mapping)) {
            return false;
        }

        if (0 != $loopIndex % $mapping[$objectsByCol]) {
            return false;
        }

        return true;
    }
}
