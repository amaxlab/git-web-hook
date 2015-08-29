<?php

/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:10.
 */
namespace AmaxLab\GitWebHook;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

/**
 * Class Repository.
 */
class Repository extends BaseCommandContainer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array|Branch[]
     */
    protected $branchesList = array();

    /**
     * @param string               $name           Name on the repository
     * @param string               $path           Path to execute commands
     * @param array                $options        Options related to current repository
     * @param array                $defaultOptions Default options passed from hook
     * @param LoggerInterface|null $logger         Logger
     */
    public function __construct($name, $path, array $options = array(), array $defaultOptions = array(), LoggerInterface $logger = null)
    {
        $this->path = $path;
        $this->name = $name;
        $this->logger = $logger ? $logger : new NullLogger();

        $this->options = array_merge($defaultOptions, $options);

        $this->logger->debug('Create repository with params '.json_encode($this->options));
    }

    /**
     * @param string      $name
     * @param array       $commands
     * @param string|null $path
     * @param array       $options
     *
     * @return $this
     */
    public function addBranch($name, array $commands, $path = null, array $options = array())
    {
        $path = $path ? $path : $this->path;

        if (!array_key_exists($name, $this->branchesList)) {
            $this->logger->info('Add branch '.$name.', path: '.$path);

            $branch = new Branch($this, $name, $path, $options, $this->options);
            $branch->addCommand($commands);
            $this->branchesList[$name] = $branch;
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return Branch
     */
    public function getBranch($name)
    {
        return array_key_exists($name, $this->branchesList) ? $this->branchesList[$name] : null;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
