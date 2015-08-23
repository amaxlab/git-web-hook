<?php
/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 22.08.15
 * Time: 20:06
 */

namespace AmaxLab\GitWebHook;

/**
 * Class CommandResult
 *
 * @package AmaxLab\GitWebHook
 */
class CommandResult
{

    /**
     * @var string
     */
    protected $command;

    /**
     * @var int
     */
    protected $resultCode;

    /**
     * @var array
     */
    protected $output;

    /**
     * @var array
     */
    protected $options;


    /**
     * @param string $command
     * @param array  $output
     * @param int    $resultCode
     * @param array  $options
     */
    public function __construct($command, array $output, $resultCode, array $options)
    {
        $this->command = $command;
        $this->output = $output;
        $this->resultCode = $resultCode;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     *
     * @return CommandResult
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return int
     */
    public function getResultCode()
    {
        return $this->resultCode;
    }

    /**
     * @param int $resultCode
     *
     * @return CommandResult
     */
    public function setResultCode($resultCode)
    {
        $this->resultCode = $resultCode;

        return $this;
    }

    /**
     * @return array
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param array $output
     *
     * @return CommandResult
     */
    public function setOutput(array $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return CommandResult
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }
}
