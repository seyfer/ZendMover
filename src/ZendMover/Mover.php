<?php

namespace ZendMover;

use ZendBaseModel\Traits\DebugMode;
use ZendMover\Command\MoveCommandInterface;

/**
 * Description of Mover
 *
 * @author  seyfer
 * @package ZendMover
 */
class Mover implements MoverInterface
{
    use DebugMode;

    const DIRECTION_FORWARD = 1;
    const DIRECTION_BACK    = 2;

    /**
     * @var int
     */
    protected $direction = self::DIRECTION_FORWARD;

    /**
     * flag
     * move or not
     *
     * @var boolean
     */
    protected $moveIt = TRUE;

    /**
     * @var array
     */
    protected $commandList = [];

    /**
     * @var int
     */
    protected $current = 0;

    /**
     * @var MoveCommandInterface
     */
    protected $currentCommand;

    /**
     * default mode for dir creation or change
     *
     * @var int
     */
    protected $defaultDirMod = 0777;

    protected $currentFilePathFrom;
    protected $currentFilePathTo;

    /**
     * @return mixed
     */
    public function getCurrentFilePathFrom()
    {
        return $this->currentFilePathFrom;
    }

    /**
     * @return mixed
     */
    public function getCurrentFilePathTo()
    {
        return $this->currentFilePathTo;
    }

    /**
     * @return MoveCommandInterface
     */
    public function getCurrentCommand()
    {
        return $this->currentCommand;
    }

    /**
     * @return int
     */
    public function getCommandListCount()
    {
        return count($this->commandList);
    }

    /**
     * @return bool
     */
    public function isMoveIt()
    {
        return $this->moveIt;
    }

    /**
     * @return bool
     */
    public function getMoveIt()
    {
        return $this->moveIt;
    }

    public function setMoveIt($moveIt)
    {
        $this->moveIt = $moveIt;
    }

    /**
     * init, check init params
     *
     * @param MoveCommandInterface $moveCommand
     */
    protected function init(MoveCommandInterface $moveCommand)
    {
        //        if (!$this->getFromDirectory() || !$this->getToDirectory()) {
        //            throw new \RuntimeException(__METHOD__ . " you need set from and to directory");
        //        }

        if (empty($moveCommand->getFilesToMove())) {
            throw new \RuntimeException(__METHOD__ . " you need set files for moving");
        }
    }

    /**
     * move files
     *
     * @param MoveCommandInterface $moveCommand
     */
    public function iLikeToMoveItMoveIt(MoveCommandInterface $moveCommand)
    {

        $this->addCommandToList($moveCommand);

        $this->direction = self::DIRECTION_FORWARD;
        $this->init($moveCommand);

        //        Debug::dump($moveCommand);

        $this->processFiles();
    }

    /**
     * move files back
     */
    public function iLikeToMoveItMoveItBack()
    {
        $moveCommand = $this->popCommandFromList();
        $moveCommand->reverseFromToDirs();

        $this->direction = self::DIRECTION_BACK;
        $this->init($moveCommand);

        $this->processFiles();
    }

    /**
     * @param MoveCommandInterface $moveCommand
     */
    private function addCommandToList(MoveCommandInterface $moveCommand)
    {
        $this->commandList[] = $moveCommand;
        $this->current++;
        $this->currentCommand = $moveCommand;
    }

    /**
     * @return MoveCommandInterface
     */
    private function popCommandFromList()
    {
        if (count($this->commandList) <= 0) {
            throw new \RuntimeException(__METHOD__ . " tried pop from empty list");
        }

        $moveCommand = array_pop($this->commandList);
        $this->current--;
        $this->currentCommand = $moveCommand;

        return $moveCommand;
    }

    /**
     * main move method
     */
    private function processFiles()
    {
        foreach ($this->currentCommand->getFilesToMove() as $fileToMove) {
            if ($fileToMove instanceof \SplFileInfo) {
                $this->processSplFileInfo($fileToMove);
            } else {
                $this->processArray($fileToMove);
            }
        }
    }

    /**
     * prepare path and do move
     *
     * @param \SplFileInfo $file
     * @return bool
     */
    private function processSplFileInfo(\SplFileInfo $file)
    {
        //clear TO directory if needed
        if ($this->getCurrentCommand()->isUsePathReplace()) {
            $this->getCurrentCommand()->setToDirectory(NULL);
        }

        //calc from directory
        $filePathFrom = $this->prepareFilePathFrom($file);

        //calc to directory
        $filePathTo = $this->prepareFilePathTo($file);

        //for debug
        $this->currentFilePathFrom = $filePathFrom;
        $this->currentFilePathTo   = $filePathTo;

        //        \Zend\Debug\Debug::dump($filePathFrom, 'filePathFrom');
        //        \Zend\Debug\Debug::dump($filePathTo, 'filePathTo');
        //        exit();

        $this->validateFileFrom($filePathFrom);
        $this->validateFileTo($filePathTo);

        if (!$this->isMoveIt()) {
            return null;
        }

        $result = rename($filePathFrom, $filePathTo);

        return $result;
    }

    /**
     * @param \SplFileInfo $file
     * @return string
     */
    private function prepareFilePathFrom(\SplFileInfo $file)
    {
        if ($this->getCurrentCommand()->isUsePathReplace() && $this->direction !== self::DIRECTION_BACK) {
            $filePathFrom = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();
        } else {
            $filePathFrom = $this->currentCommand->getFromDirectory() . $file->getFilename();
        }

        return $filePathFrom;
    }

    /**
     * if to directory not exists
     *
     * @param \SplFileInfo $file
     * @return mixed
     * @throws \Exception
     */
    private function prepareFilePathTo(\SplFileInfo $file)
    {
        if ($this->currentCommand->isUsePathReplace()) {

            $fileWhereToMovePath = $file->getPath() . DIRECTORY_SEPARATOR;

            if ($this->direction === self::DIRECTION_FORWARD) {
                $filePathTo = $this->currentCommand->replacePath($fileWhereToMovePath);
            } elseif ($this->direction === self::DIRECTION_BACK) {
                $filePathTo = $this->currentCommand->replacePathBack($fileWhereToMovePath);
            } else {
                throw new \Exception(__METHOD__ . " wrong direction");
            }

            //fuck
            $this->currentCommand->setToDirectory($filePathTo);
        } else {
            $filePathTo = $this->currentCommand->getToDirectory();
        }

        if (!file_exists($filePathTo) && !is_dir($filePathTo)) {
            mkdir($filePathTo, $this->defaultDirMod, TRUE);
        } else {
            chmod($filePathTo, $this->defaultDirMod);
        }

        if ($this->currentCommand->getDestinationFileName()) {
            $fileName = $this->currentCommand->getDestinationFileName();
        } else {
            $fileName = $file->getFilename();
        }

        $filePathTo .= $fileName;

        return $filePathTo;
    }

    /**
     * maybe array
     *
     * @deprecated
     * @param  $fileToMove
     * @return bool
     */
    private function processArray($fileToMove)
    {
        $fileName = $fileToMove['name'];

        $filePathFrom = $this->currentCommand->getFromDirectory() . $fileName;
        $filePathTo   = $this->currentCommand->getToDirectory() . $fileName;

        $this->validateFileFrom($filePathFrom);
        $this->validateFileTo($filePathTo);

        return rename($filePathFrom, $filePathTo);
    }

    /**
     * @param $filePathTo
     */
    private function validateFileTo($filePathTo)
    {
        if (!$filePathTo instanceof \SplFileInfo) {
            $filePathTo = new \SplFileInfo($filePathTo);
        }

        if (!is_writable($filePathTo->getPath())) {
            throw new \RuntimeException(__METHOD__ . " " . $filePathTo .
                                        " not writable");
        }
    }

    /**
     * @param $filePathFrom
     */
    private function validateFileFrom($filePathFrom)
    {
        if (!file_exists($filePathFrom)) {
            throw new \RuntimeException(__METHOD__ . " " . $filePathFrom .
                                        " not exist");
        }

        if (!is_readable($filePathFrom)) {
            throw new \RuntimeException(__METHOD__ . " " . $filePathFrom .
                                        " not readable");
        }
    }

}
