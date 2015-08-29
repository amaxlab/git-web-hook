<?php

/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 29.08.15
 * Time: 16:03.
 */
namespace AmaxLab\GitWebHook;

/**
 * Interface CommandContainerInterface.
 */
interface CommandContainerInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @return array|Command[]
     */
    public function getCommands();

    /**
     * @param string|array|string[] $command
     *
     * @return $this
     */
    public function addCommand($command);
}
