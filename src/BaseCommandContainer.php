<?php

/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 29.08.15
 * Time: 16:16.
 */
namespace AmaxLab\GitWebHook;

/**
 * Class BaseCommandContainer.
 */
class BaseCommandContainer implements CommandContainerInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array|Command[]
     */
    protected $commandsList = array();

    /**
     * @param string $name
     * @param string $path
     * @param array  $options
     * @param array  $defaultOptions
     */
    public function __construct($name, $path, array $options = array(), array $defaultOptions = array())
    {
        $this->path = $path;
        $this->name = $name;
        $this->options = array_merge($defaultOptions, $options); // TODO resolve options to exclude wrong configuration
        $this->commandsList = array();
    }

    /**
     * @param array|string[]|string $command
     *
     * @return $this
     */
    public function addCommand($command)
    {
        if (is_array($command)) {
            foreach ($command as $cmd) {
                $this->addCommand($cmd);
            }
        }

        if (is_string($command)) {
            $command = new Command($command);
            $this->commandsList[] = $command;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array|Command[]
     */
    public function getCommands()
    {
        return $this->commandsList;
    }
}
