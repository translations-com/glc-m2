<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 9/3/2019
 * Time: 10:06 AM
 */

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportTranslationsCommand extends Command
{
    private $importTranslations;

    public function __construct(
        \TransPerfect\GlobalLink\Cron\ImportTranslations $importTranslations
    ) {
        $this->importTranslations = $importTranslations;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:translations:import')
            ->setDescription('Import Translations')
            ->setHelp("1. Imports all ready translations to the specified store views and into the front end of the platform.");

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        $output->writeln('Importing ready submissions...');
        try {
            $result = $this->importTranslations->executeCli();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        $output->writeln('');
    }
}
