<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:42
 */

namespace AmaxLab\GitWebHook;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Psr\Log\LoggerInterface;


/**
 * Class Branch
 *
 * @package AmaxLab\GitWebHook
 */
class Branch
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $name;

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
     * @param Repository $repository Repository owen this branch
     * @param string     $name       Name of branch
     * @param string     $path       path for root directory of repository
     * @param array      $options    options
     */
    public function __construct(Repository $repository, $name, $path, array $options = array())
    {
        $this->repository = $repository;
        $this->path = $path;
        $this->name = $name;

        $resolver = new OptionsResolver();
        $resolver->setDefaults($repository->getOptions());
        $this->options = $resolver->resolve($options);

        $this->logger = $repository->getLogger();

        $this->logger->debug('Create branch with params ' . json_encode($this->options));
    }

    /**
     * @return Repository
     */
    public function getParent()
    {
        return $this->repository;
    }

    /**
     * @param string|array $command command for a run
     * @param string       $path    path from run the command (if is null the path equal repository path)
     *
     * @return Branch
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
            $this->logger->info('Add branch command ' . $command . ', path: ' . $path);

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
        $this->logger->info('Execute commands for branch ' . $this->name . ' ...');

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
