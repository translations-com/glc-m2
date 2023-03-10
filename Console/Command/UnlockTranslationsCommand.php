<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        \TransPerfect\GlobalLink\Cron\SubmitTranslations $submitTranslations,
        \TransPerfect\GlobalLink\Cron\ReceiveTranslations $receiveTranslations,
        \TransPerfect\GlobalLink\Cron\CancelTranslations $cancelTranslations
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
            return 1;
        }
        $output->writeln('');

        $output->writeln('Unlocking retrievals...');
        try {
            $this->receiveTranslations->unlockJob();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return 1;
        }
        $output->writeln('');

        $output->writeln('Unlocking cancellations...');
        try {
            $this->cancelTranslations->unlockJob();
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return 1;
        }
        $output->writeln('');
        return 0;
    }
}
