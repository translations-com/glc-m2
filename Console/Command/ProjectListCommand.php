<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TransPerfect\GlobalLink\Model\TranslationService;

class ProjectListCommand extends Command
{

    /**
     * @var TranslationService
     */
    private $translationService;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(TranslationService $translationService, ScopeConfigInterface $scopeConfig)
    {
        $this->translationService = $translationService;
        $this->scopeConfig = $scopeConfig;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:project:list')->setDescription('List GlobalLink projects.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projects = $this->translationService->getProjects();
        $output->writeIn(var_export($projects, true));
    }
}
