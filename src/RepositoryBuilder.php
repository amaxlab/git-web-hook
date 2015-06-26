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
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $options;

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
}