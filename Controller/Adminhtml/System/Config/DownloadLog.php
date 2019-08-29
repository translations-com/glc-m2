<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 8/22/2019
 * Time: 10:17 AM
 */

namespace TransPerfect\GlobalLink\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Psr\Log\LoggerInterface;

class DownloadLog extends Action
{
    protected $resultRawFactory;

    protected $fileFactory;

    protected $loggerInterface;

    protected $directory;

    public function __construct(
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context,
        LoggerInterface $loggerInterface,
        \Magento\Framework\Filesystem\DirectoryList $directory
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->loggerInterface = $loggerInterface;
        $this->directory = $directory;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $fileName = 'transperfect_globallink.log';
            $path = $this->directory->getPath("log").'/'.$fileName;
            if (file_exists($path)) {
                $file = $this->fileFactory->create(
                    $fileName,
                    @file_get_contents($path));
            } else {
                $file = null;
            }
            return $file;
        } catch(\Exception $exception){
            $this->loggerInterface->critical($exception);
        }
    }
}