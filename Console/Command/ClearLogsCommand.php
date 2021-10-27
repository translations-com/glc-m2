<?php

namespace TransPerfect\GlobalLink\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class ClearLogsCommand extends Command
{
    protected $file;
    protected $fileSystem;
    protected $directoryList;
    protected $driverFile;

    /**
     * UnlockTranslationsCommand constructor.
     * @param Magento\Framework\Filesystem\Io\File $file
     */

    public function __construct(
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\Driver\File $driverFile
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->fileSystem = $fileSystem;
        $this->driverFile = $driverFile;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('globallink:logs:clear')
            ->setDescription('Clears logs created before the 1st of the current month')
            ->setHelp("This deletes any logs created before the 1st of the current month");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentDate = date('m-d-Y', time());
        $currentMonth = date('m');
        $currentYear = date('Y');
        $files = $this->getDirectoryContents();
        $output->writeln('');
        $output->writeln('Clearing logs...');
        try {
            foreach($files as $file){
                $absoluteFileName = substr($file, strrpos($file, '/') + 1);
                $absoluteFileName = substr($absoluteFileName, 0, strrpos($absoluteFileName, "."));
                if (strpos($absoluteFileName, 'transperfect_globallink_') !== false) {
                    $absoluteFileName = str_replace('transperfect_globallink_', '', $absoluteFileName);
                    $dateArray = explode('-', $absoluteFileName);
                    if($currentMonth == 1 && $currentYear > $dateArray[2]){
                        if($this->file->rm($file)){
                            $output->writeln('Log has been deleted: ' . $file);
                        } else{
                            $output->writeln('Could not delete log: ' . $file);
                        }
                    } elseif($currentMonth != 1 && $currentMonth > $dateArray[0]){
                        if($this->file->rm($file)){
                            $output->writeln('Log has been deleted: ' . $file);
                        } else{
                            $output->writeln('Could not delete log: ' . $file);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }
        $output->writeln('Logs deletion has finished.');
    }

    public function getDirectoryContents($path = '/log/') {
        $files = [];
        try {
            //get the base folder path you want to scan (replace var with pub / media or any other core folder)
            $path = $this->directoryList->getPath('var') . $path;
            $files =  $this->driverFile->readDirectory($path);
        } catch (FileSystemException $e) {
            $this->logger->error($e->getMessage());
        }

        return $files;
    }
}
