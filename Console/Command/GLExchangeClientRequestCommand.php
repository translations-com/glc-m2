<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputOptionFactory;

use Magento\Framework\App\Config\ScopeConfigInterface;
use TransPerfect\GlobalLink\Model\SoapClient\GLExchangeClient;

class GLExchangeClientRequestCommand extends Command
{
    /**
     * @var GLExchangeClient
     */
    protected $glExchangeClient;

    /**
     * @var InputOptionFactory
     */
    protected $inputOptionFactory;

    const KEY_ENDPOINT = 'endpoint';
    const KEY_METHOD = 'method';
    const KEY_PARAM = 'param';

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
        $this->setName('globallink:test:glexchangeclient_request')
            ->setDescription('Test request using GLExchange library')
            ->setDefinition($this->getOptionsList())
            ->setHelp("--endpoint and --method options are required".PHP_EOL
            ."Parameters supposed to be sent in the Method should be defined using --param in this nonation <comment>paramName[[paramValue]]</comment>.".PHP_EOL
            ."Example:".PHP_EOL
            ."You want to send 2 parameters: title=Robocop and material=Steel and meat.".PHP_EOL
            ."Then you should do it like that:".PHP_EOL
            ."<comment>--param='title[[Robocop]]' --param='material[[Steel and meat]]'</comment>".PHP_EOL
            ."You can't send several parameters with same paramName. Only last one will be used.");

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        $endpoint = trim($input->getOption(self::KEY_ENDPOINT));
        $method = trim($input->getOption(self::KEY_METHOD));
        $params = $input->getOption(self::KEY_PARAM);

        if (empty($endpoint) || empty($method)) {
            $output->writeln('<error>--endpoint and --method options are required</error>');
            $output->writeln(PHP_EOL.'try <comment>globallink:test:glexchangeclient_request --help</comment>'.PHP_EOL.'to read more');
            $output->writeln('');
            return;
        }

        $parameters = [];
        foreach ($params as $param) {
            preg_match('|^(.+)\[\[(.+)\]\]$|', $param, $matches);

            if (!empty($matches[1]) && !empty($matches[2])) {
                $parameters[$matches[1]] = $matches[2];
            }
        }

        try {
            $response = $this->glExchangeClient->request($endpoint, $method, $parameters);
            print_r($response);
        } catch (\Exception $e) {
            $output->writeln('<error>Error: '.$e->getMessage().'</error>');
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
            self::KEY_ENDPOINT,
            null,
            InputOption::VALUE_REQUIRED,
            'Service connection endpoint'
        );
        $option2 = new InputOption(
            self::KEY_METHOD,
            null,
            InputOption::VALUE_REQUIRED,
            'Service Method name'
        );
        $option3 = new InputOption(
            self::KEY_PARAM,
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Parameters supposed to be given into the Method'
        );

        return [
            $option1,
            $option2,
            $option3
        ];
    }
}
