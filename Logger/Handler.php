<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Logger;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseLogger;

/**
 * Class Handler
 *
 * @package TransPerfect\GlobalLink\Logger
 */
class Handler extends BaseLogger
{
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
