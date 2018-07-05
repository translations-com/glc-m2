<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use TransPerfect\GlobalLink\Cron\ReceiveTranslations;

class ReceiveTranslationsCommand extends Command
{
    public function __construct(
        ReceiveTranslations $receiveTranslations
    ) {
        $this->receiveTranslations = $receiveTranslations;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:translations:receive')
            ->setDescription('Receive Translations')
            ->setHelp("Receive all finished translations from service and write data into stores");

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

            $output->writeln('Receiving all finished translations...');
        try {
            $result = $this->receiveTranslations->executeCli();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        $output->writeln('');
    }
}
