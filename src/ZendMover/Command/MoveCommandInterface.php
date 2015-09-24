<?php
/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 21.01.15
 * Time: 18:12
 */

namespace ZendMover\Command;

/**
 * Interface MoveCommandInterface
 *
 * @package ZendMover\Command
 */
interface MoveCommandInterface
{

    public function execute();

    public function unexecute();

    public function isUsePathReplace();

    /**
     * @return string
     */
    public function getFromDirectory();

    /**
     * @param string $fromDirectory
     * @return $this
     */
    public function setFromDirectory($fromDirectory);

    /**
     * @return string
     */
    public function getToDirectory();

    /**
     * @param string $toDirectory
     * @return $this
     */
    public function setToDirectory($toDirectory);

    /**
     * @return array
     */
    public function getFilesToMove();

    /**
     * @param array $filesToMove
     * @return $this
     */
    public function setFilesToMove($filesToMove);

    /**
     * @param \SplFileInfo $fileToMove
     * @return mixed
     */
    public function addFileToMove(\SplFileInfo $fileToMove);

    /**
     * @return $this
     */
    public function convertFilesToSpl();

    /**
     * inspect file directory and set it
     *
     * @param $file
     * @return $this
     */
    public function setToDirectoryFromFile($file);

    /**
     * inspect file directory and set it
     *
     * @param $file
     * @return $this
     */
    public function setFromDirectoryFromFile($file);

    public function reverseFromToDirs();

    /**
     * @param $filePathFrom
     * @return mixed
     */
    public function replacePath($filePathFrom);

    /**
     * @param $filePathFrom
     * @return mixed
     */
    public function replacePathBack($filePathFrom);

    public function getDestinationFileName();

    /**
     * @param $destinationFileName
     * @return $this
     */
    public function setDestinationFileName($destinationFileName);
}