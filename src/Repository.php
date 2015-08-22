<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:10
 */

namespace AmaxLab\GitWebHook;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;


/**
 * Class Repository
 *
 * @package AmaxLab\GitWebHook
 */
class Repository
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array|Command[]
     */
    protected $commandsList = array();

    /**
     * @var array|Branch[]
     */
    protected $branchesList = array();

    /**
     * @param string          $name           Name on the repository
     * @param string          $path           Path to execute commands
     * @param array           $options        Options related to current repository
     * @param array           $defaultOptions Default options passed from hook
     * @param LoggerInterface $logger         Logger
     */
    public function __construct($name, $path, array $options = array(), array $defaultOptions = array(), LoggerInterface $logger)
    {
        $this->path = $path;
        $this->name = $name;
        $this->logger = $logger?$logger:new NullLogger();

        $this->options = array_merge($defaultOptions, $options);;

        $this->logger->debug('Create repository with params ' . json_encode($this->options));
    }

    /**
     * @param string $name
     * @param array  $commands
     * @param string $path
     * @param array  $options
     *
     * @return $this
     */
    public function addBranch($name, array $commands, $path = null, array $options = array())
    {
        $path = $path?$path:$this->path;

        if (!array_key_exists($name, $this->branchesList)) {
            $this->logger->info('Add branch ' . $name . ', path: ' . $path);

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
     * @param string|array $command command for a run
     *
     * @return Repository
     */
    public function addCommand($command)
    {
        if (is_array($command)) {
            foreach ($command as $cmd) {
                $this->addCommand($cmd);
            }
        } else {
            $this->logger->info('Add to repository command: ' . $command);

            $command = new Command($command, $this->logger);
            $this->commandsList[] = $command;
        }

        return $this;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function executeCommands($path)
    {
        $path = $this->path?$this->path:$path;

        $this->logger->info('Execute commands for repository ' . $this->name . ' ...');
        $result = array();

        foreach ($this->commandsList as $command) {
            $result[] = $command->execute($path, $this->options);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
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
}
