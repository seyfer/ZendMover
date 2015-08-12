<?php

namespace ZendMover;

use ZendMover\Command\MoveCommandInterface;

/**
 * Just Move It!
 *
 * @author  seyfer
 * @package ZendMover
 */
interface MoverInterface
{

    /**
     * move it!
     *
     * @param MoveCommandInterface $moveCommand
     * @return
     */
    public function iLikeToMoveItMoveIt(MoveCommandInterface $moveCommand);

    /**
     * move it back!
     *
     * @return mixed
     */
    public function iLikeToMoveItMoveItBack();
}
