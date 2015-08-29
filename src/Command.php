<?php

/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:18.
 */
namespace AmaxLab\GitWebHook;

use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string          $command command for execution
     * @param LoggerInterface $logger  logger
     */
    public function __construct($command, LoggerInterface $logger)
    {
        $this->command = $command;
        $this->logger = $logger;
    }

    /**
     * @param string $path
     * @param array  $options
     *
     * @return CommandResult
     */
    public function execute($path, array $options)
    {
        if (!chdir($path)) {
            $this->logger->error('Cannot change directory to '.$path);

            return new CommandResult($this->command, array(), 1, $options);
        }

        $this->logger->info('Execute command '.$this->command.' from '.$path);

        exec($this->command, $out, $resultCode);
        $out = is_array($out) ? $out : array();

        $this->logger->info('Exit code =  '.$resultCode);
        if ($resultCode !== 0) {
            $this->logger->error('Cannot execute command '.$this->command.' from '.$path);
        }

        return new CommandResult($this->command, $out, $resultCode, $options);
    }
}
