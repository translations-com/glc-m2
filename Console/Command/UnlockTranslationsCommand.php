<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use TransPerfect\GlobalLink\Cron\CancelTranslations;
use TransPerfect\GlobalLink\Cron\SubmitTranslations;
use TransPerfect\GlobalLink\Cron\ReceiveTranslations;

class UnlockTranslationsCommand extends Command
{
    /**
     * @var \TransPerfect\GlobalLink\Cron\SubmitTranslations
     */
    protected $submitTranslations;

    /**
     * @var \TransPerfect\GlobalLink\Cron\ReceiveTranslations
     */
    protected $receiveTranslations;

    /**
     * @var \TransPerfect\GlobalLink\Cron\CancelTranslations
     */

    protected $cancelTranslations;

    /**
     * UnlockTranslationsCommand constructor.
     * @param SubmitTranslations $submitTranslations
     * @param ReceiveTranslations $receiveTranslations
     * @param CancelTranslations $cancelTranslations
     */

    public function __construct(
        SubmitTranslations $submitTranslations,
        ReceiveTranslations $receiveTranslations,
        CancelTranslations $cancelTranslations
    ) {
        $this->submitTranslations = $submitTranslations;
        $this->receiveTranslations = $receiveTranslations;
        $this->cancelTranslations = $cancelTranslations;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:translations:unlock')
            ->setDescription('Unlock Submit & Receive Translations')
            ->setHelp("This unlocks both submitting and receiving of translations in case of an exception");

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        $output->writeln('Unlocking submissions...');
        try {
            $this->submitTranslations->unlockJob();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }
        $output->writeln('');

        $output->writeln('Unlocking retrievals...');
        try {
            $this->receiveTranslations->unlockJob();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }
        $output->writeln('');

        $output->writeln('Unlocking cancellations...');
        try {
            $this->cancelTranslations->unlockJob();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }
        $output->writeln('');
    }
}
