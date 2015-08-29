<?php

/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 29.08.15
 * Time: 16:02.
 */
namespace AmaxLab\GitWebHook;

use Psr\Log\LoggerInterface;

/**
 * Class CommandExecutor.
 */
class CommandExecutor
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param CommandContainerInterface|array|CommandContainerInterface[] $commandContainer
     *
     * @return array|CommandResult[]
     */
    public function execute($commandContainer)
    {
        $result = array();
        if (is_array($commandContainer)) {
            foreach ($commandContainer as $container) {
                $result = array_merge($result, $this->execute($container));
            }
        }

        if ($commandContainer instanceof CommandContainerInterface) {
            $reflection = new \ReflectionClass($commandContainer);
            $className = $reflection->getShortName();
            $this->logger->info('Execute commands for '.$className.' '.$commandContainer->getName().' ...');

            $commandList = $commandContainer->getCommands();
            foreach ($commandList as $command) {
                $result[] = $this->executeCommand($command, $commandContainer->getPath(), $commandContainer->getOptions());
            }
        }

        return $result;
    }

    /**
     * @param Command $command
     * @param string  $path
     * @param array   $options
     *
     * @return CommandResult
     */
    private function executeCommand(Command $command, $path, array $options)
    {
        $oldCwd = getcwd();

        $this->logger->info('Execute command '.$command->getCommand().' from '.$path);

        if (!chdir($path)) {
            $this->logger->error('Cannot change directory to '.$path);

            return new CommandResult($command->getCommand(), array(), 1, $options);
        }

        exec($command->getCommand(), $out, $resultCode);
        $out = is_array($out) ? $out : array();

        $this->logger->info('Exit code =  '.$resultCode);
        if ($resultCode !== 0) {
            $this->logger->error('Cannot execute command '.$command->getCommand().' from '.$path);
        }

        chdir($oldCwd);

        return new CommandResult($command->getCommand(), $out, $resultCode, $options);
    }
}
