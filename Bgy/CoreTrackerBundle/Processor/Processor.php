<?php
/**
 * @author Boris Guéry <guery.b@gmail.com>
 */

namespace Bgy\CoreTrackerBundle\Processor;

use Bgy\CoreTracker\CoreDump;
use Bgy\CoreTracker\Filter\FilterStrategyInterface;
use Bgy\CoreTracker\Sorter\SorterStrategyInterface;

class Processor implements ProcessorInterface
{
    protected $filter;

    protected $sorter;

    public function __construct(FilterStrategyInterface $filter = null,
                                SorterStrategyInterface $sorter = null)
    {
        $this->sorter   = $sorter;
        $this->filter   = $filter;
    }

    public function process(CoreDump $coredump)
    {
        $this->sorter->sort($coredump);

        /** @var $collectedClass \Bgy\CoreTracker\CollectedClass */
        foreach ($coredump->getCollectedData() as $collectedClass) {
            if ($this->filter->shouldBeFiltered($collectedClass)) {
                unset($coredump[$collectedClass->className]);
                continue;
            }
        }
    }
}
