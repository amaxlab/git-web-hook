<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:10
 */

namespace AmaxLab\GitWebHook;

use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
     * @param array           $defaultOptions Default options passed from hook options
     * @param LoggerInterface $logger         Logger
     */
    public function __construct($name, $path, array $options = array(), array $defaultOptions = array(), LoggerInterface $logger)
    {
        $this->path = $path;
        $this->name = $name;
        $this->logger = $logger?$logger:new NullLogger();

        $resolver = new OptionsResolver();
        $resolver->setDefaults($defaultOptions);
        $this->options = $resolver->resolve($options);

        $this->logger->debug('Create repository with params ' . json_encode($this->options));
    }

    /**
     * @param string $name
     * @param string $path
     * @param array  $options
     *
     * @return Branch
     */
    public function addBranch($name, $path = '', array $options = array())
    {
        if (!$path) {
            $path = $this->path;
        }

        if (!isset($this->branchesList[$name])) {
            $this->logger->info('Add branch ' . $name . ', path: ' . $path);

            $this->branchesList[$name] = new Branch($this, $name, $path, $options);
        }

        return $this->branchesList[$name];
    }

    /**
     * @param string $name
     *
     * @return bool|Branch
     */
    public function getBranch($name)
    {
        return isset($this->branchesList[$name]) ? $this->branchesList[$name] : false;
    }

    /**
     * @param string|array $command command for a run
     * @param string       $path    path from run the command
     *
     * @return Repository
     */
    public function addCommand($command, $path = '')
    {
        if (!$path) {
            $path = $this->path;
        }

        if (is_array($command)) {
            foreach ($command as $cmd) {
                $this->addCommand($cmd, $path);
            }
        } else {
            $this->logger->info('Add to repository command: ' . $command . ', path: ' . $path);

            $command = new Command($command, $path, $this->logger);
            $this->commandsList[] = $command;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function executeCommands()
    {
        $this->logger->info('Execute commands for repository ' . $this->name . ' ...');
        $result = array();
        if (!empty($this->commandsList)) {
            foreach ($this->commandsList as $command) {
                $result[] = $command->execute();
            }
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getCommandsCount()
    {
        return count($this->commandsList);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getOptions($name = '')
    {
        if ($name) {
            return (isset($this->options[$name])) ? $this->options[$name] : '';
        }

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
