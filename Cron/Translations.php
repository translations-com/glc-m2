<?php
namespace TransPerfect\GlobalLink\Cron;

use Magento\Bundle\Model\Option as BundleOption;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Option as ProductOption;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator as ProductUrlPathGenerator;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Glob;
use Magento\Framework\Filesystem\Io\File;
use Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory as ReviewCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use TransPerfect\GlobalLink\Helper\Data as HelperData;
use TransPerfect\GlobalLink\Logger\BgTask\Logger as BgLogger;
use TransPerfect\GlobalLink\Model\ResourceModel\Category\Attribute\CollectionCustomFactory as CategoryAttributeCollectionFactory;
use TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;
use TransPerfect\GlobalLink\Model\ResourceModel\Product\Attribute\CollectionCustomFactory as ProductAttributeCollectionFactory;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\CollectionFactory as QueueCollectionFactory;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\ItemFactory as ItemResourceFactory;
use TransPerfect\GlobalLink\Model\TranslationService;

/**
 * Class Translations
 */
abstract class Translations
{
    /**
     * @var \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory
     */
    protected $reviewCollectionFactory;

    /**
     * Queue collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\CollectionFactory
     */
    protected $queueCollectionFactory;

    /**
     * Item collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * Item resource factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\ItemFactory
     */
    protected $itemResourceFactory;

    /**
     * Field collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\field\CollectionFactory
     */
    protected $fieldCollectionFactory;

    /**
     * @var \TransPerfect\GlobalLink\Model\TranslationService
     */
    protected $translationService;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\DomDocument\DomDocumentFactory
     */
    protected $domDocumentFactory;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $blockFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $pageCollectionFactory;
    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\CollectionFactory
     */
    protected $blockCollectionFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Framework\App\State $appStat
     */
    protected $appState;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $productAttributeCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory
     */
    protected $categoryAttributeCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Option
     */
    protected $productOption;

    /**
     * @var \Magento\Bundle\Model\Option
     */
    protected $bundleOption;

    /**
     * @var \TransPerfect\GlobalLink\Logger\BgTask\Logger
     */
    protected $bgLogger;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $out;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator
     */
    protected $productUrlPathGenerator;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var false|string[]
     */
    protected $loggingLevels;

    protected $allowDuplicateSubmissions;
    /**
     * Translations constructor.
     *
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\CollectionFactory                    $queueCollectionFactory
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory               $itemCollectionFactory
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\ItemFactory                          $itemResourceFactory
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory                    $fieldCollectionFactory
     * @param \TransPerfect\GlobalLink\Model\TranslationService                                       $translationService
     * @param \Magento\Framework\Filesystem                                                           $filesystem
     * @param \Magento\Framework\DomDocument\DomDocumentFactory                                       $domDocumentFactory
     * @param \Magento\Cms\Model\BlockFactory                                                         $blockFactory
     * @param \Magento\Cms\Model\PageFactory                                                          $pageFactory
     * @param \Magento\Store\Model\StoreManagerInterface                                              $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                                      $scopeConfig
     * @param \TransPerfect\GlobalLink\Helper\Data                                                    $helper
     * @param \Magento\Eav\Api\AttributeRepositoryInterface                                           $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder                                            $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder                                                    $filterBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                                         $productRepository
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface                                        $categoryRepository
     * @param \Magento\Framework\App\State                                                            $appState
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Product\Attribute\CollectionCustomFactory  $productAttributeCollectionFactory
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Category\Attribute\CollectionCustomFactory $categoryAttributeCollectionFactory
     * @param \Magento\Catalog\Model\Product\Option                                                   $productOption
     * @param \Magento\Bundle\Model\Option                                                            $bundleOption
     * @param \TransPerfect\GlobalLink\Logger\BgTask\Logger                                           $bgLogger
     * @param \Magento\Framework\Filesystem\Io\File                                                   $file
     * @param \Symfony\Component\Console\Output\ConsoleOutput                                         $out
     * @param \Magento\Framework\Event\ManagerInterface                                               $eventManager
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator                                $productUrlPathGenerator
     * @param \Magento\Framework\Registry                                                             $registry
     * @param \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory                    $reviewCollectionFactory
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory                                 $pageCollectionFactory
     * @param \Magento\Cms\Model\ResourceModel\Block\CollectionFactory                                $blockCollectionFactory
     */
    public function __construct(
        QueueCollectionFactory $queueCollectionFactory,
        ItemCollectionFactory $itemCollectionFactory,
        ItemResourceFactory $itemResourceFactory,
        FieldCollectionFactory $fieldCollectionFactory,
        TranslationService $translationService,
        Filesystem $filesystem,
        DomDocumentFactory $domDocumentFactory,
        BlockFactory $blockFactory,
        PageFactory $pageFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        HelperData $helper,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\App\State $appState,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory,
        ProductOption $productOption,
        BundleOption $bundleOption,
        BgLogger $bgLogger,
        File $file,
        ConsoleOutput $out,
        EventManagerInterface $eventManager,
        ProductUrlPathGenerator $productUrlPathGenerator,
        \Magento\Framework\Registry $registry,
        ReviewCollectionFactory $reviewCollectionFactory,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory,
        \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $blockCollectionFactory
    ) {
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->itemResourceFactory = $itemResourceFactory;
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->translationService = $translationService;
        $this->filesystem = $filesystem;
        $this->domDocumentFactory = $domDocumentFactory;
        $this->blockFactory = $blockFactory;
        $this->pageFactory = $pageFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->appState = $appState;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->categoryAttributeCollectionFactory = $categoryAttributeCollectionFactory;
        $this->productOption = $productOption;
        $this->bundleOption = $bundleOption;
        $this->bgLogger = $bgLogger;
        $this->file = $file;
        $this->out = $out;
        $this->eventManager = $eventManager;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->loggingLevels = $this->scopeConfig->getValue('globallink/general/logging_level') == null ? [''] : explode(',', $this->scopeConfig->getValue('globallink/general/logging_level'));
        $this->registry = $registry;
        if ($this->scopeConfig->getValue('globallink/general/allow_duplicate_submissions') == 1) {
            $this->allowDuplicateSubmissions = true;
        } else {
            $this->allowDuplicateSubmissions = false;
        }
    }

    /**
     * Delete all files in folder
     *
     * $param string $dirPath
     */
    protected function clearDir($dirPath)
    {
        $files = Glob::glob($dirPath . '/*');
        foreach ($files as $file) {
            $this->file->rmdirRecursive($file);
        }
    }

    /**
     * Show error while run from command line
     *
     * @param string $message
     */
    protected function cliMessage($message, $type = null)
    {
        if (!empty($this->mode) && $this->mode == 'cli') {
            if ($type=='error') {
                $message = '<error>' . $message . '</error>';
            }
            $this->out->writeln($message);
        }
    }

    /**
     * Get fields to translate wrapper
     *
     * @param int $entityTypeId
     * @param int $entityId
     *
     * @return array
     */
    public function getFields($entityTypeId, $entityId = null)
    {
        if (method_exists($this, 'getFieldsToTranslate')) {
            return $this->getFieldsToTranslate($entityTypeId, $entityId);
        }
    }

    /**
     * Retunrns lock file name
     *
     * @return string
     */
    protected function getLockFileName()
    {
        return static::LOCK_FILE_NAME;
    }
    /**
     * get whether the submit queue is locked
     */
    public function isJobLocked()
    {
        $lockFolder = $this->translationService->getLockFolder();
        $filePath = $lockFolder . '/' . $this->getLockFileName();

        if ($this->file->fileExists($filePath, true)) {
            return true;
        }
        return false;
    }
    /**
     * Check lock file for existance
     * Create one if it doesn't exist
     * Returns false if job currently locked or lock file can't be created
     *
     * @return bool
     */
    protected function lockJob()
    {
        $lockFolder = $this->translationService->getLockFolder();
        $filePath = $lockFolder . '/' . $this->getLockFileName();

        if ($this->file->fileExists($filePath, true)) {
            $message = 'Lock file found. Previous run is not finished yet. Exit.';
            $logData = ['message' => $message];
            if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            $this->cliMessage($message);
            return false;
        }

        if (!$this->file->write($filePath, 'lock')) {
            $message ="Can't create lock file " . $filePath . ", please check permissions.";
            $logData = ['message' => $message];
            if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            $this->cliMessage($message);
            return false;
        }

        return true;
    }

    /**
     * Unlock job for next run
     */
    public function unlockJob()
    {
        $lockFolder = $this->translationService->getLockFolder();
        $filePath = $lockFolder . '/' . $this->getLockFileName();

        if (!$this->file->rm($filePath)) {
            $message ="Can't remove lock file " . $filePath . ", please check permissions.";
            $logData = ['message' => $message];
            if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            $this->cliMessage($message);
        }
    }

    /**
     * Update Queue status
     * Try to get items of current queue with any status that means item not finished
     * if no such items exists queue status is downloaded (finished)
     *
     * @param \TransPerfect\GlobalLink\Model\Queue $queue
     */
    protected function tryToSetQueueFinished(\TransPerfect\GlobalLink\Model\Queue $queue)
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $queue->getItems()]
        );
        $items->addFieldToFilter(
            'status_id',
            ['nin' => [
                \TransPerfect\GlobalLink\Model\Queue\Item::STATUS_FINISHED,
                \TransPerfect\GlobalLink\Model\Queue\Item::STATUS_APPLIED,
                \TransPerfect\GlobalLink\Model\Queue\Item::STATUS_FOR_DELETE
            ]]
        );
        $foundItems = count($items);

        if ($foundItems == 0) {
            $queue->setStatus(\TransPerfect\GlobalLink\Model\Queue::STATUS_FINISHED);
            $queue->save();
        }
    }
}
