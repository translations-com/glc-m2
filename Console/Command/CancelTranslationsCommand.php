<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CancelTranslationsCommand extends Command
{
    protected $cancelTranslations;

    public function __construct(
        \TransPerfect\GlobalLink\Cron\CancelTranslations $cancelTranslations
    ) {
        $this->cancelTranslations = $cancelTranslations;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:translations:cancel')
            ->setDescription('Cancel Translations')
            ->setHelp("1. Send cancel call for all locally cancelled translations. 2. Receive all remotely cancelled targets and update their local statuses");

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

            $output->writeln('Cancelling submissions...');
        try {
            $result = $this->cancelTranslations->executeCli();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return 1;
        }

        $output->writeln('');
        return 0;
    }
}
