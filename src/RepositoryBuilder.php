<?php
/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 19.06.15
 * Time: 12:04
 */

namespace AmaxLab\GitWebHook;


class RepositoryBuilder
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
    protected $options;

    /**
     * @var array|string
     */
    protected $commands;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->options = array();
    }

    /**
     * @param Hook $hook
     *
     * @return Repository
     */
    public function build(Hook $hook)
    {
        $repository = new Repository($this->getName(), $this->getPath(), $this->getOptions(), $hook->getOptions(), $hook->getLogger());

        if ($this->commands) {
            $repository->addCommand($this->commands);
        }

        return $repository;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

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
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @return array|string
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param array|string $commands
     *
     * @return RepositoryBuilder;
     */
    public function setCommands($commands)
    {
        $this->commands = $commands;

        return $this;
    }
}