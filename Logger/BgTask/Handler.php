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
    const LOG_FILE_NAME = 'globallink_api_request.log';

    /**
     * constructor
     *
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     */
    public function __construct(
        DriverInterface $filesystem
    ) {
        $this->fileName = DIRECTORY_SEPARATOR.DirectoryList::VAR_DIR
            .DIRECTORY_SEPARATOR.DirectoryList::LOG
            .DIRECTORY_SEPARATOR.self::LOG_FILE_NAME;

        parent::__construct($filesystem);
    }
}
