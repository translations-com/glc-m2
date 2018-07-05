<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use TransPerfect\GlobalLink\Cron\SubmitTranslations;

class SubmitTranslationsCommand extends Command
{
    /**
     * @var \TransPerfect\GlobalLink\Cron\SubmitTranslations
     */
    protected $submitTranslations;

    public function __construct(
        SubmitTranslations $submitTranslations
    ) {
        $this->submitTranslations = $submitTranslations;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:translations:submit')
            ->setDescription('Submit Translations')
            ->setHelp("Send all unsent items from all new and unfinished queues to translation service");

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

            $output->writeln('Submitting all queues to service...');
        try {
            $this->submitTranslations->executeCli();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        $output->writeln('');
    }
}
