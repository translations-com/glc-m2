<?php
namespace TransPerfect\GlobalLink\Logger\BgTask;

use Monolog\Logger as MonologLogger;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;

    /**
     * Log file name
     */
    const LOG_FILE_NAME = 'transperfect_globallink.log';
    protected $fileName;

    /**
     * constructor
     *
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     */
    public function __construct(
        DriverInterface $filesystem
    ) {
        $logFileName = 'transperfect_globallink_'.date('m-d-Y').'.log';
        $this->fileName = DIRECTORY_SEPARATOR.DirectoryList::VAR_DIR
            .DIRECTORY_SEPARATOR.DirectoryList::LOG
            .DIRECTORY_SEPARATOR.$logFileName;

        parent::__construct($filesystem);
    }
}
