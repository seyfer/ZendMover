<?php
/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 9/24/15
 * Time: 2:44 PM
 */

namespace ZendMover;

/**
 * Class Copier
 * @package ZendMover
 */
class Copier extends Mover implements MoverInterface
{
    /**
     * @param $filePathFrom
     * @param $filePathTo
     * @return bool
     */
    protected function doSystemCommand($filePathFrom, $filePathTo)
    {
        return copy($filePathFrom, $filePathTo);
    }
}