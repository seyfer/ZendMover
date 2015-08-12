<?php
/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 22.01.15
 * Time: 12:27
 */

namespace ZendMoverTest;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use ZendMover\Command\MoveCommand;
use ZendMover\Mover;

/**
 * Class MoverTest
 *
 * @package ZendMoverTest
 */
class MoverTest extends BaseTest
{
    /**
     * @var Mover
     */
    private $mover;

    /**
     * @var MoveCommand
     */
    private $moveCommand;

    private function prepareVfs()
    {
        //virtual file system for tests
        vfsStreamWrapper::register();
        $root = vfsStreamWrapper::setRoot(new vfsStreamDirectory("data"));

        //        $structure = [
        //            'dir1' => [
        //                'file1' => 'some text content',
        //            ],
        //            'dir2' => [
        //                'file2' => 'some text content',
        //            ],
        //        ];
        //
        //        vfsStream::create($structure, $root);

        vfsStream::copyFromFileSystem($this->testDataPath, $root);
    }

    public function setUp()
    {
        parent::setUp();

        $this->prepareVfs();

        $this->mover = new Mover();
    }

    /**
     * @param null $file
     * @param      $from
     * @param      $to
     */
    private function prepareMoveCommand($file = null, $from, $to)
    {
        $this->moveCommand = new MoveCommand($this->mover);
        $this->moveCommand->addFileToMove($file);

        $this->moveCommand->setFromDirectory($from);
        $this->moveCommand->setToDirectory($to);
    }

    /**
     * @param null $file
     * @param      $from
     * @param      $to
     */
    private function prepareReplaceMoveCommand($file = null, $from, $to)
    {
        $this->moveCommand = new MoveCommand($this->mover);
        $this->moveCommand->addFileToMove($file);

        $this->moveCommand->setFromDirectory($from);
        //        $this->moveCommand->setToDirectory($to);

        $this->moveCommand->setUsePathReplace(TRUE);
        $this->moveCommand->setPathReplaceParts(["search" => "dir1", "replace" => "dir3"]);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp #.*exist.*#
     */
    public function testCommandAddEmptyFile()
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->prepareMoveCommand(new \SplFileInfo(null), null, null);
    }

    /**
     * test move logic with path replace
     */
    public function testMoveCommandWithPathReplace()
    {
        $file1 = new \SplFileInfo(vfsStream::url('data/dir1/file1'));
        $this->prepareReplaceMoveCommand($file1, vfsStream::url('data/dir1/'), null);
        if (!$file1->isReadable()) {
            return;
        }

        $this->executeMoving($file1);

        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir3/file1'));
    }

    /**
     * check if command adds
     */
    public function testMoveCommandAddToListAndMove()
    {
        $file1 = new \SplFileInfo(vfsStream::url('data/dir1/file1'));
        $this->prepareMoveCommand($file1, vfsStream::url('data/dir1/'), vfsStream::url('data/dir2/'));
        if (!$file1->isReadable()) {
            return;
        }

        $this->executeMoving($file1);

        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir2/file1'));
    }

    /**
     * @return Mover
     */
    private function prepareMoverMock()
    {
        /**
         * @var Mover $moverMock
         */
        $moverMock = $this->getMockBuilder(Mover::class)
                          ->setMethods(null)
                          ->getMock();

        //        $moverMock->expects($this->once())
        //                  ->method('init')
        //                  ->willReturn(TRUE);

        return $moverMock;
    }

    /**
     * do the job
     *
     * @param $file
     * @return Mover
     */
    private function executeMoving($file)
    {
        $this->assertNotEmpty($this->moveCommand->getFilesToMove());

        $moverMock = $this->prepareMoverMock();
        $this->moveCommand->setMover($moverMock);
        $this->moveCommand->execute();

        $moverMock = $this->moveCommand->getMover();

        $this->assertEquals(1, $moverMock->getCommandListCount());
        $this->assertEquals($this->moveCommand, $moverMock->getCurrentCommand());
        $this->assertEquals($file, $this->moveCommand->getFilesToMove()[0]);

        return $moverMock;
    }

    /**
     * @return Mover
     */
    private function unexecuteMoving()
    {
        $this->assertNotEmpty($this->moveCommand->getFilesToMove());

        $this->moveCommand->unexecute();

        $moverMock = $this->moveCommand->getMover();

        $this->assertEquals(0, $moverMock->getCommandListCount());
        $this->assertEquals($this->moveCommand, $moverMock->getCurrentCommand());

        return $moverMock;
    }

    /**
     * check if command adds
     */
    public function testMoveCommandAddToListAndMove2()
    {
        $file2 = new \SplFileInfo(vfsStream::url('data/dir2/file2'));
        $this->prepareMoveCommand($file2, vfsStream::url('data/dir2/'), vfsStream::url('data/dir1/'));
        if (!$file2->isReadable()) {
            return;
        }

        $this->executeMoving($file2);

        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir2/file2'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file2'));
    }

    /**
     *
     */
    public function testMoveToAndBack()
    {
        //        // Stop here and mark this test as incomplete.
        //        $this->markTestIncomplete(
        //            'This test has not been implemented yet.'
        //        );

        $file1 = new \SplFileInfo(vfsStream::url('data/dir1/file1'));
        $this->prepareMoveCommand($file1, vfsStream::url('data/dir1/'), vfsStream::url('data/dir2/'));
        if (!$file1->isReadable()) {
            return;
        }

        $this->executeMoving($file1);

        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir2/file1'));

        //back
        $this->mover       = new Mover();
        $this->moveCommand = new MoveCommand($this->mover);

        $file1 = new \SplFileInfo(vfsStream::url('data/dir2/file1'));
        $this->prepareMoveCommand($file1, vfsStream::url('data/dir2/'), vfsStream::url('data/dir1/'));
        if (!$file1->isReadable()) {
            return;
        }

        $this->executeMoving($file1);

        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir2/file1'));
    }

    public function testExecuteAndUnexecute()
    {
        $file1 = new \SplFileInfo(vfsStream::url('data/dir1/file1'));
        $this->prepareMoveCommand($file1, vfsStream::url('data/dir1/'), vfsStream::url('data/dir2/'));
        if (!$file1->isReadable()) {
            return;
        }

        $this->executeMoving($file1);

        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir2/file1'));

        $this->unexecuteMoving();

        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir2/file1'));
    }

    /**
     * test move logic with path replace
     */
    public function testExecuteAndUnexecuteWithPathReplace()
    {
        $file1 = new \SplFileInfo(vfsStream::url('data/dir1/file1'));
        $this->prepareReplaceMoveCommand($file1, vfsStream::url('data/dir1/'), null);
        if (!$file1->isReadable()) {
            return;
        }

        $this->executeMoving($file1);

        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir3/file1'));

        $this->unexecuteMoving();

        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('data/dir1/file1'));
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('data/dir3/file1'));
    }

    public function tearDown()
    {
        vfsStreamWrapper::register();
    }
}