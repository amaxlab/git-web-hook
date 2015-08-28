<?php

/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:06.
 */
namespace AmaxLab\GitWebHook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Class Hook.
 */
class Hook
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array|Repository[]
     */
    protected $repositoryList = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array|Command[]
     */
    protected $commandsList = array();

    /**
     * Constructor.
     *
     * @param string          $path    global path
     * @param array           $options hook options
     * @param LoggerInterface $logger  logger
     * @param Request         $request Symfony request object
     */
    public function __construct($path = null, array $options = array(), LoggerInterface $logger = null, Request $request = null)
    {
        $this->path = $path ? $path : getcwd();
        $this->request = $request ? $request : Request::createFromGlobals();
        $this->logger = $logger ? $logger : new NullLogger();
        $this->options = $this->validateOptions($options);

        $this->logger->debug('Create hook with params '.json_encode($this->options));
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
     * @param string $configFile path to main configuration file
     */
    public function loadConfig($configFile)
    {
        $yaml = new Parser();
        try {
            $config = $yaml->parse(file_get_contents($configFile));
        } catch (ParseException $e) {
            $this->logger->error(sprintf('Unable to parse the YAML string: %s in %s', $e->getMessage(), $e->getParsedFile()));

            return;
        }
        if (!is_array($config)) {
            $this->logger->error('Parsed config file is not an array');

            return;
        }

        $config = $this->resolveMainConfig($config);

        if (array_key_exists('trustedProxies', $config)) {
            Request::setTrustedProxies($config['trustedProxies']);
        }

        //global hook options
        if (array_key_exists('options', $config)) {
            $options = $config['options'];
            $this->options = $this->validateOptions($options);
        }

        // global hook commands
        if (array_key_exists('commands', $config)) {
            $this->addCommand($config['commands']);
        }

        if (array_key_exists('path', $config)) {
            $this->path = $config['path'];
        }

        if (array_key_exists('repositories', $config)) {
            $this->handleRepositoryConfig($config);
        }
    }

    /**
     * @param string $dir
     *
     * @return int Count of loaded repositories
     */
    public function loadRepos($dir)
    {
        $files = glob($dir.DIRECTORY_SEPARATOR.'*.yml');
        $count = 0;

        foreach ($files as $file) {
            $yaml = new Parser();
            try {
                $config = $yaml->parse(file_get_contents($file));
            } catch (ParseException $e) {
                $this->logger->error(sprintf('Unable to parse the YAML string: %s in %s', $e->getMessage(), $e->getParsedFile()));
                continue;
            }

            $count += $this->handleRepositoryConfig($config);
        }

        return $count;
    }

    /**
     * @param string $name
     * @param string $path
     * @param array  $options
     * @param null   $commands
     *
     * @return Repository
     */
    public function addRepository($name, $path = null, array $options = array(), $commands = null)
    {
        $path = $path ? $path : $this->path;

        $repository = new Repository($name, $path, $options, $this->options, $this->logger);
        $commands && $repository->addCommand($commands);

        if (!isset($this->repositoryList[$repository->getName()])) {
            $this->logger->info(sprintf('Add repository %s, path: %s', $repository->getName(), $repository->getPath()));

            $this->repositoryList[$repository->getName()] = $repository;
        }

        return $this->repositoryList[$repository->getName()];
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
        }

        if (is_string($command)) {
            $this->logger->info('Add hook command '.$command);

            $command = new Command($command, $this->logger);
            $this->commandsList[] = $command;
        }

        return $this;
    }

    /**
     * Handle git web hook query.
     *
     * @param Event $event
     */
    public function execute(Event $event = null)
    {
        $event = $event ? $event : $this->createEvent();
        $this->logger->info('Starting web hook handle');
        $commandsResult = array();

        if (!$event->isValid()) {
            $this->logger->error('Found not valid event from '.$event->getHost());
            $this->return404();

            return;
        }

        if (!$this->checkPermissions($event, $this->options)) {
            return;
        }

        $repository = $this->getRepository($event->getRepositoryName());
        if (!$repository || !($branch = $repository->getBranch($event->getBranchName()))) {
            $this->logger->warning('Repository: '.$event->getRepositoryName().' and branch: '.$event->getBranchName().' not found in the settings');
            $this->return404();

            return;
        }

        $commandsResult['hook'] = $this->executeCommands();
        $commandsResult['repository'] = $repository->executeCommands($this->path);
        $commandsResult['branch'] = $branch->executeCommands($repository->getPath());

        $this->sendEmails($event, $commandsResult);

        $this->logger->info('End of web hook handle');
    }

    /**
     * @param string $name
     *
     * @return Repository|null
     */
    public function getRepository($name)
    {
        return array_key_exists($name, $this->repositoryList) ? $this->repositoryList[$name] : null;
    }

    /**
     * @param string|array $where
     * @param string       $that
     *
     * @return bool
     */
    private function checkAllow($where, $that)
    {
        if (is_array($where)) {
            $this->logger->info('Checking permissions '.$that.', in: '.var_export($where, true));

            return  in_array($that, $where) ? true : false;
        }

        if ($where == '*' || (trim($where) == trim($that))) {
            $this->logger->info('Checking permissions '.$that.', in: '.$where);

            return true;
        }

        return false;
    }

    /**
     * @param Event $event
     * @param array $options
     *
     * @return bool
     */
    private function checkPermissions(Event $event, array $options)
    {
        $securityCode = $options['securityCode'];
        $author = $options['allowedAuthors'];
        $host = $options['allowedHosts'];
        if ($securityCode && $securityCode != $event->getSecurityCode()) {
            $this->logger->warning('Security code not match');
            $this->logger->debug('Config: '.$securityCode.' != $_GET:'.$event->getSecurityCode());

            return false;
        }

        if (!$this->checkAllow($host, $event->getHost())) {
            $this->logger->warning('Host '.$event->getHost().' not allowed on this branch');

            return false;
        }

        if (!$this->checkAllow($author, $event->getAuthor())) {
            $this->logger->warning('Author '.$event->getAuthor().' not allowed on this branch');

            return false;
        }

        return true;
    }

    /**
     * @param Event                 $event
     * @param string                $to
     * @param string                $from
     * @param array|CommandResult[] $resultCommands
     */
    private function sendEmail(Event $event, $to, $from, array $resultCommands)
    {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: '.$from,
        );

        $subject = $event->getRepositoryName().'('.$event->getBranchName().')';

        $template = new Template(__DIR__.'/templates/', $this->logger);

        $message = $template->render('mail.php', array(
            'subject' => $subject,
            'event' => $event,
            'resultCommands' => $resultCommands,
        ));

        $this->logger->info('Send email to '.$to.' subject '.$subject);
        $this->logger->debug('Text of email: '.$message);

        if (!mail($to, $subject, $message, implode($headers, "\r\n"))) {
            $this->logger->error('Cannot send email to '.$to);
        }
    }

    /**
     * @param Event                   $event
     * @param array|CommandResult[][] $results
     */
    private function sendEmails(Event $event, array $results)
    {
        $mailParts = array();
        foreach ($results as $resultCommands) {
            foreach ($resultCommands as $resultCommand) {
                $options = $resultCommand->getOptions();

                if (!$options['sendEmails']) {
                    continue;
                }

                $mailRecipients = $options['mailRecipients'];
                if ($options['sendEmailAuthor']) {
                    $mailRecipients[] = $event->getAuthor();
                }
                $mailRecipients = array_unique($mailRecipients);
                if (empty($mailRecipients) || empty($resultCommand->getOutput())) {
                    continue;
                }

                foreach ($mailRecipients as $recipient) {
                    if (!array_key_exists($recipient, $mailParts)) {
                        $mailParts[$recipient] = array();
                    }
                    $mailParts[$recipient][] = $resultCommand;
                }
            }
        }

        foreach ($mailParts as $mail => $parts) {
            $this->sendEmail($event, $mail, $this->options['sendEmailFrom'], $parts);
        }
    }

    /**
     * @return array
     */
    private function executeCommands()
    {
        $this->logger->info('Execute commands for hook ...');
        $result = array();
        foreach ($this->commandsList as $command) {
            $result[] = $command->execute($this->path, $this->options);
        }

        return $result;
    }

    /**
     * @return Event
     */
    private function createEvent()
    {
        return new Event($this->logger, $this->request, array(
            'securityCodeFieldName' => $this->options['securityCodeFieldName'],
            'repositoryFieldName' => $this->options['repositoryFieldName'],
        ));
    }

    /**
     * Return 404 error.
     */
    private function return404()
    {
        $this->logger->warning('Returning 404 error');
        $response = new Response(null, 404);
        $response->send();
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function validateOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'sendEmails' => false,
            'sendEmailAuthor' => false,
            'sendEmailFrom' => 'git-web-hook@'.$this->request->getHost(),
            'mailRecipients' => array(),
            'allowedAuthors' => array(),
            'allowedHosts' => array(),
            'securityCode' => '',
            'securityCodeFieldName' => 'code',
            'repositoryFieldName' => 'url',
        ));

        return $resolver->resolve($options);
    }

    /**
     * @param array $config
     *
     * @return int
     */
    private function handleRepositoryConfig($config)
    {
        $count = 0;
        if (is_array($config) && array_key_exists('repositories', $config)) {
            foreach ($config['repositories'] as $repoName => $repoConf) {
                $repoConf = $this->resolveRepositoryConfig($repoConf);
                $repository = $this->addRepository($repoName, $repoConf['path'], $repoConf['options'], $repoConf['commands']);
                ++$count;

                // adding branches
                if (array_key_exists('branch', $repoConf)) {
                    foreach ($repoConf['branch'] as $branchName => $branchOptions) {
                        $branchOptions = $this->resolveBranchConfig($branchOptions);
                        $repository->addBranch($branchName, $branchOptions['commands'], $branchOptions['path'], $branchOptions['options']);
                    }
                }
            }
        }

        return $count;
    }

    /**
     * @param array $mainConfig
     *
     * @return array
     */
    private function resolveMainConfig(array $mainConfig)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(array(
                'path' => null,
                'commands' => array(),
                'options' => array(),
            ))
            ->setDefined(array(
                'trustedProxies',
                'repositoriesDir',
                'repositories',
            ))
            ->setAllowedTypes('trustedProxies', 'array')
            ->setAllowedTypes('repositoriesDir', 'string')
            ->setAllowedTypes('commands', 'array')
            ->setAllowedTypes('options', 'array')
            ->setAllowedTypes('repositories', 'array')
            ->setAllowedTypes('path', array('string', 'null'))
        ;

        return $resolver->resolve($mainConfig);
    }

    /**
     * @param array $repositoryConfig
     *
     * @return array
     */
    private function resolveRepositoryConfig(array $repositoryConfig)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(array(
                'path' => null,
                'commands' => array(),
                'options' => array(),
                'branch' => array(),
            ))
            ->setAllowedTypes('commands', 'array')
            ->setAllowedTypes('options', 'array')
            ->setAllowedTypes('branch', 'array')
            ->setAllowedTypes('path', array('string', 'null'))
        ;

        return $resolver->resolve($repositoryConfig);
    }

    /**
     * @param array $branchConfig
     *
     * @return array
     */
    private function resolveBranchConfig(array $branchConfig)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(array(
                'path' => null,
                'commands' => array(),
                'options' => array(),
            ))
            ->setAllowedTypes('commands', 'array')
            ->setAllowedTypes('options', 'array')
            ->setAllowedTypes('path', array('string', 'null'))
        ;

        return $resolver->resolve($branchConfig);
    }
}
