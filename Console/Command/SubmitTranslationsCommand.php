<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class SubmitTranslationsCommand extends Command
{
    const ddOverride = 'ddOverride';
    /**
     * @var \TransPerfect\GlobalLink\Cron\SubmitTranslations
     */
    protected $submitTranslations;

    public function __construct(
        \TransPerfect\GlobalLink\Cron\SubmitTranslations $submitTranslations
    ) {
        $this->submitTranslations = $submitTranslations;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:translations:submit')
            ->setDescription('Submit Translations')
            ->setHelp("Send all unsent items from all new and unfinished queues to translation service");
        $this->addOption(
            self::ddOverride,
            null,
            InputOption::VALUE_OPTIONAL,
            'Due-date override for past due-dates',
            5
        );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Submitting all queues to service...');
        try {
            if($ddOverride = $input->getOption('ddOverride')){
                $this->submitTranslations->executeCli($ddOverride);
            } else {
                $this->submitTranslations->executeCli();
            }
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        $output->writeln('');
    }
}
