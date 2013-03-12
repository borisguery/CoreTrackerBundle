<?php
/**
 * @author Boris Guéry <guery.b@gmail.com>
 */

namespace Bgy\CoreTrackerBundle\Command;

use Bgy\CoreTracker\Dumper\ClassCollectionDumper;
use Bgy\CoreTracker\Filter\CallThresholdFilterStrategy;
use Bgy\CoreTracker\Filter\ChainedFilterStrategy;
use Bgy\CoreTracker\Filter\NamespaceFilterStrategy;
use Bgy\CoreTracker\Sorter\CallSorterStrategy;
use Bgy\CoreTracker\Sorter\ClassNameSorterStrategy;
use Bgy\CoreTrackerBundle\Processor\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('bgy:coretracker:dump')
            ->setDescription('Display statistic on Core Dump')
            ->addArgument('core-dump', InputArgument::REQUIRED, 'The Core Dump file')
            ->addOption('sort-namespace', 'f', InputOption::VALUE_NONE, 'Sort by namespace')
            ->addOption('sort-calls',     'c', InputOption::VALUE_NONE, 'Sort by call count')
            ->addOption('reverse',        'r', InputOption::VALUE_NONE, 'Reverse sort order')
            ->addOption('threshold',      't', InputOption::VALUE_REQUIRED, 'Ignore when calls reach the threshold', -1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ((false !== $input->getOption('sort-namespace') && false !== $input->getOption('sort-calls')) && ($input->getOption('sort-namespace') xor $input->getOption('sort-calls'))) {

            throw new \InvalidArgumentException('Sort attributes are exclusive');
        }

        $sorter = $input->getOption('sort-calls')
            ? new CallSorterStrategy($input->getOption('reverse'))
            : new ClassNameSorterStrategy($input->getOption('reverse'))
        ;

        $coredumpFile = $input->getArgument('core-dump');

        $threshold = (int) $input->getOption('threshold');

        if (!is_file($coredumpFile)) {

            throw new \RuntimeException(sprintf('"%s" is not a valid file.', $coredumpFile));
        }

        $coredump = unserialize(file_get_contents($coredumpFile));

        $filters = array();
        // At least we remove ourselves
        $filters[] = new NamespaceFilterStrategy(array('Bgy\CoreTracker'), false);

        if ($threshold > -1) {
            $filters[] = new CallThresholdFilterStrategy($input->getOption('threshold'));
        }

        $filter = new ChainedFilterStrategy($filters);

        $processor = new Processor($filter, $sorter);
        $processor->process($coredump);

        $dumper = new ClassCollectionDumper();

        echo $dumper->dump($coredump);
    }
}
