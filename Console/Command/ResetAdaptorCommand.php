<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\ObjectManagerInterface;

class ResetAdaptorCommand extends Command
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Magento\Framework\Setup\SchemaSetupInterface
     */
    protected $setup;

    /**
     * ResetAdaptorCommand constructor.
     * @param ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     */

    public function __construct(ObjectManagerInterface $objectManager, \Magento\Framework\Setup\SchemaSetupInterface $setup) {
        $this->objectManager = $objectManager;
        $this->setup = $setup;
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
        $question = $this->objectManager->create('\Symfony\Component\Console\Question\ConfirmationQuestion', ['question'=>'Are you sure you want to reset adaptor? Only store locales will not be cleared. (y/n) ', 'default' => FALSE]);
        echo $ans = $helper->ask($input, $output, $question);
        if($ans == 'y') {
            try {
                $this->setup->getConnection()->query("DELETE FROM core_config_data WHERE path LIKE 'globallink%'");
                $this->setup->getConnection()->query("DELETE FROM globallink_entity_translation_status WHERE 1");
                $this->setup->getConnection()->query("DELETE FROM globallink_field WHERE 1");
                $this->setup->getConnection()->query("DELETE FROM globallink_job_items WHERE 1");
                $this->setup->getConnection()->query("DELETE FROM globallink_job_item_status WHERE 1");
                $this->setup->getConnection()->query("DELETE FROM globallink_job_item_status_history WHERE 1");
                $this->setup->getConnection()->query("DELETE FROM globallink_job_queue WHERE 1");
                $this->setup->getConnection()->query("DELETE FROM globallink_field_product_category WHERE 1");
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
            $output->writeln('');

            $output->writeln('Adaptor reset successfully.');

            $output->writeln('');
        }
    }
}
