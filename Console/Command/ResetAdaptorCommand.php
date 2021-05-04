<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Eav\Setup\EavSetupFactory;

class ResetAdaptorCommand extends Command
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;
    protected $connection;

    /**
     * ResetAdaptorCommand constructor.
     * @param ObjectManagerInterface $objectManager
     */

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager) {
        $this->objectManager = $objectManager;
        $this->resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection = $this->resource->getConnection();
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:adaptor:reset')
            ->setDescription('Reset adaptor to a fresh install state.')
            ->setHelp("This will reset the adaptor to a fresh install state, deleting all GlobalLink data. Please be 100% sure before running.");

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $helper = $this->getHelper('question');
        $question = $this->objectManager->create('\Symfony\Component\Console\Question\ConfirmationQuestion', ['question'=>'Are you sure you want to reset adaptor? Only field configuration will not be cleared. (y/n) ', 'default' => FALSE]);
        $ans = $helper->ask($input, $output, $question);
        if($ans == 'y') {
            try {
                $this->connection->query("DELETE FROM core_config_data WHERE path LIKE 'globallink%'");
                $this->connection->query("DELETE FROM globallink_entity_translation_status WHERE 1");
                $this->connection->query("DELETE FROM globallink_job_items WHERE 1");
                $this->connection->query("DELETE FROM globallink_job_item_status WHERE 1");
                $this->connection->query("DELETE FROM globallink_job_item_status_history WHERE 1");
                $this->connection->query("DELETE FROM globallink_job_queue WHERE 1");
                $this->connection->query("UPDATE store SET locale=NULL WHERE 1");
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
            $output->writeln('');

            $output->writeln('Adaptor reset successfully.');

            $output->writeln('');
        }
    }
}
