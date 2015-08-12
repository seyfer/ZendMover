<?php
/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 21.01.15
 * Time: 18:12
 */

namespace ZendMover\Command;


use ZendMover\Mover;

/**
 * Class AbstractMoveCommand
 *
 * @package ZendMover\Command
 */
abstract class AbstractMoveCommand implements MoveCommandInterface
{
    /**
     * @var string
     */
    protected $fromDirectory = '';

    /**
     * @var string
     */
    protected $toDirectory = '';

    /**
     * @var array
     */
    protected $filesToMove = [];

    /**
     * @var bool
     */
    protected $usePathReplace = FALSE;

    /**
     * @var array
     */
    protected $pathReplaceParts
        = [
            "search"  => "",
            "replace" => "",
        ];

    /**
     * @var Mover
     */
    protected $mover;

    /**
     * @param Mover $mover
     */
    public function __construct(Mover $mover)
    {
        $this->setMover($mover);
    }

    /**
     * @return bool
     */
    public function isUsePathReplace()
    {
        return $this->usePathReplace;
    }

    /**
     * @param $filePathFrom
     * @return mixed
     */
    public function replacePath($filePathFrom)
    {
        $filePathTo = str_replace(
            $this->pathReplaceParts['search'], $this->pathReplaceParts['replace'], $filePathFrom
        );

        return $filePathTo;
    }

    /**
     * @param $filePathFrom
     * @return mixed
     */
    public function replacePathBack($filePathFrom)
    {
        $filePathTo = str_replace(
            $this->pathReplaceParts['replace'], $this->pathReplaceParts['search'], $filePathFrom
        );

        return $filePathTo;
    }

    /**
     * @param boolean $usePathReplace
     * @return $this
     */
    public function setUsePathReplace($usePathReplace)
    {
        $this->usePathReplace = $usePathReplace;

        return $this;
    }

    /**
     * @param array $pathReplaceParts
     * @return $this
     */
    public function setPathReplaceParts($pathReplaceParts)
    {
        $this->pathReplaceParts = $pathReplaceParts;

        return $this;
    }

    /**
     * @return Mover
     */
    public function getMover()
    {
        return $this->mover;
    }

    /**
     * @param Mover $mover
     * @return $this
     */
    public function setMover(Mover $mover)
    {
        $this->mover = $mover;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilesToMove()
    {
        return $this->filesToMove;
    }

    /**
     *
     * @param  \stdClass|Array $filesToMove
     * @return $this
     * @throws \RuntimeException
     */
    public function setFilesToMove($filesToMove)
    {
        if (is_object($filesToMove)) {
            if (method_exists($filesToMove, 'toArray')) {
                $filesToMove = $filesToMove->toArray();
            } else {
                throw new \RuntimeException(__METHOD__ . " need array as param");
            }
        }

        if (is_array($filesToMove)) {
            $this->filesToMove = $filesToMove;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function convertFilesToSpl()
    {
        if (!$this->filesToMove) {
            return;
        }

        $converted = [];
        foreach ($this->filesToMove as $fileToMove) {
            if ($fileToMove instanceof \SplFileInfo) {
                $converted[] = $fileToMove;
            } else {
                $convertedFile = new \SplFileInfo($fileToMove);
                $converted[]   = $convertedFile;
            }
        }

        $this->filesToMove = $converted;

        return $this;
    }

    /**
     * @param \SplFileInfo $fileToMove
     * @return $this
     */
    public function addFileToMove(\SplFileInfo $fileToMove)
    {
        if (!$fileToMove->isFile()) {
            //            Debug::dump($fileToMove);

            throw new \RuntimeException(__METHOD__ . " added file not exist");
        }

        $this->filesToMove[] = $fileToMove;

        return $this;
    }

    private function emptyFileList()
    {
        $this->filesToMove = [];
    }

    /**
     * @return string
     */
    public function getFromDirectory()
    {
        return $this->fromDirectory;
    }

    /**
     * @return string
     */
    public function getToDirectory()
    {
        return $this->toDirectory;
    }

    /**
     * inspect file directory and set it
     *
     * @param $file
     * @return $this
     */
    public function setToDirectoryFromFile($file)
    {
        $fileDir = $this->getFilePath($file);

        $this->setToDirectory($fileDir);

        return $this;
    }

    /**
     * inspect file directory and set it
     *
     * @param $file
     * @return $this
     */
    public function setFromDirectoryFromFile($file)
    {
        $fileDir = $this->getFilePath($file);

        $this->setFromDirectory($fileDir);

        return $this;
    }

    /**
     * @param \SplFileInfo|string $file
     * @return string
     */
    protected function getFilePath($file)
    {
        if (!$file) {
            throw new \InvalidArgumentException(__METHOD__ . $file .
                                                " param invalid");
        }

        if (!$file instanceof \SplFileInfo) {
            $file = new \SplFileInfo($file);
        }

        $fileDir = $file->getPath();

        return $fileDir;
    }

    /**
     *
     * @param  $toDirectory
     * @return $this
     */
    public function setToDirectory($toDirectory)
    {
        if (!$toDirectory) {
            return;
        }

        try {
            $this->checkIsDir($toDirectory);
        } catch (\Exception $e) {
            $this->prepareDir($toDirectory);
        }

        $this->checkIsWritable($toDirectory);

        $this->toDirectory = $toDirectory;

        return $this;
    }

    /**
     * @param $path
     * @throws \Exception
     */
    private function prepareDir($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new \Exception('Directory of ' . $path . ' was not created');
            }
        } else {
            if (substr(sprintf('%o', fileperms($path)), -4) != '0777') {
                if (!chmod($path, 0777)) {
                    throw new \Exception('Cannot change access to ' . $path);
                }
            }
        }
    }

    /**
     *
     * @param  $fromDirectory
     * @return $this
     */
    public function setFromDirectory($fromDirectory)
    {
        if (!$fromDirectory) {
            return;
        }

        $this->checkIsDir($fromDirectory);

        $this->fromDirectory = $fromDirectory;

        return $this;
    }

    /**
     *
     * @param  $dir
     * @return boolean
     * @throws \InvalidArgumentException
     */
    protected function checkIsWritable($dir)
    {
        if (!is_writable($dir)) {
            throw new \InvalidArgumentException(__METHOD__ . " " . $dir .
                                                " is not writable");
        }

        return TRUE;
    }

    /**
     *
     * @param  $dir
     * @return boolean
     * @throws \InvalidArgumentException
     */
    protected function checkIsDir($dir)
    {
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(__METHOD__ . $dir .
                                                " not is dir");
        }

        return TRUE;
    }

    public function reverseFromToDirs()
    {
        $tmpFrom = $this->getFromDirectory();
        $this->setFromDirectory($this->getToDirectory());
        $this->setToDirectory($tmpFrom);
    }

    /**
     * default
     */
    public function execute()
    {
        $this->mover->iLikeToMoveItMoveIt($this);
    }

    /**
     * default
     */
    public function unexecute()
    {
        $this->mover->iLikeToMoveItMoveItBack($this);
    }
}