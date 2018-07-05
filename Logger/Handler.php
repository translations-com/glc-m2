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
