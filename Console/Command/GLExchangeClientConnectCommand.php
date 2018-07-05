<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputOptionFactory;

use Magento\Framework\App\Config\ScopeConfigInterface;
use TransPerfect\GlobalLink\Model\SoapClient\GLExchangeClient;

class GLExchangeClientConnectCommand extends Command
{
    /**
     * @var GLExchangeClient
     */
    protected $glExchangeClient;

    /**
     * @var InputOptionFactory
     */
    protected $inputOptionFactory;

    const KEY_USERNAME = 'username';
    const KEY_PASSWORD = 'password';
    const KEY_URL = 'url';

    public function __construct(
        GLExchangeClient $glExchangeClient,
        InputOptionFactory $inputOptionFactory
    ) {
        $this->glExchangeClient = $glExchangeClient;
        $this->inputOptionFactory = $inputOptionFactory;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:test:glexchangeclient_connect')
            ->setDescription('Test connect using GLExchange library')
            ->setDefinition($this->getOptionsList())
            ->setHelp(
                'Use command without options to test connection with real data from database.'.PHP_EOL
                ."Use options to test connection using alternative values".PHP_EOL
                ."globallink:test:glexchangeclient_connect --username='...' --password='...' --url='...'".PHP_EOL
                .'either all or none options have to be given</comment>'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        $username = trim($input->getOption(self::KEY_USERNAME));
        $password = trim($input->getOption(self::KEY_PASSWORD));
        $url = trim($input->getOption(self::KEY_URL));

        if (empty($username) && empty($password) && empty($url)) {
            $output->writeln('Connect using data from DB...');
            try {
                $result = $this->glExchangeClient->getConnect();
                $output->writeln("<info>Connection successfull</info>");
            } catch (\Exception $e) {
                $output->writeln('<error>Connection failed. '.$e->getMessage().'</error>');
            }
        } else {
            if (empty($username) || empty($password) || empty($url)) {
                $output->writeln('<error>Either all or none options ('.self::KEY_USERNAME.', '.self::KEY_PASSWORD.', '.self::KEY_URL.') have to be given</error>');
                $output->writeln('');
                return;
            }
            $output->writeln('Connect using given data...');
            $error = $this->glExchangeClient->testConnectError($username, $password, $url);
            if (empty($error)) {
                $output->writeln("<info>Connection successfull</info>");
            } else {
                $output->writeln('<error>'.$error.'</error>');
            }
        }

        $output->writeln('');
    }

    /**
     * Get list of arguments for the command
     *
     * @return InputOption[]
     */
    public function getOptionsList()
    {
        $option1 = new InputOption(
            self::KEY_USERNAME,
            null,
            InputOption::VALUE_OPTIONAL,
            'Username for connect to service'
        );
        $option2 = new InputOption(
            self::KEY_PASSWORD,
            null,
            InputOption::VALUE_OPTIONAL,
            'Password for connect to service'
        );
        $option3 = new InputOption(
            self::KEY_URL,
            null,
            InputOption::VALUE_OPTIONAL,
            'Url for connect to service'
        );

        return [
            $option1,
            $option2,
            $option3
        ];
    }
}
