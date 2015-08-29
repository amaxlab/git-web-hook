<?php

/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:18.
 */
namespace AmaxLab\GitWebHook;

/**
 * Class Command.
 */
class Command
{
    /**
     * @var string
     */
    protected $command;

    /**
     * @param string $command command for execution
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }
}
