<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:42
 */

namespace AmaxLab\GitWebHook;

use Psr\Log\LoggerInterface;


/**
 * Class Branch
 *
 * @package AmaxLab\GitWebHook
 */
class Branch
{

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
     * @param Repository $repository     Repository owen this branch
     * @param string     $name           Name of branch
     * @param string     $path           path for root directory of repository
     * @param array      $options        options
     * @param array      $defaultOptions Options passed from repository
     */
    public function __construct(Repository $repository, $name, $path, array $options = array(), array $defaultOptions)
    {
        $this->path = $path;
        $this->name = $name;

        $this->options = array_merge($defaultOptions, $options);

        $this->logger = $repository->getLogger();

        $this->logger->debug('Create branch with params ' . json_encode($this->options));
    }

    /**
     * @param string|array $command command for a run
     *
     * @return Branch
     */
    public function addCommand($command)
    {
        if (is_array($command)) {
            foreach ($command as $cmd) {
                $this->addCommand($cmd);
            }
        } else {
            $this->logger->info('Add branch command ' . $command );

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

        $this->logger->info('Execute commands for branch ' . $this->name . ' ...');

        $result = array();
        foreach ($this->commandsList as $command) {
            $result[] = $command->execute($path, $this->options);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
