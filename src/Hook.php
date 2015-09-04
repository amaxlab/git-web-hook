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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Class Hook.
 */
class Hook extends BaseCommandContainer
{
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
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @var Configurator
     */
    protected $configurator;

    /**
     * Constructor.
     *
     * @param string|null          $path    global path
     * @param array                $options hook options
     * @param LoggerInterface|null $logger  logger
     * @param Request|null         $request Symfony request object
     */
    public function __construct($path = null, array $options = array(), LoggerInterface $logger = null, Request $request = null)
    {
        $this->path = $path ? $path : getcwd();
        $this->request = $request ? $request : Request::createFromGlobals();
        $this->logger = $logger ? $logger : new NullLogger();
        $this->configurator = new Configurator($this->logger, $this->request);
        $this->options = $this->configurator->resolveOptions($options);
        $this->commandExecutor = new CommandExecutor($this->logger);

        $this->logger->debug('Create hook with params '.json_encode($this->options));
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
            $this->logger->critical('Unable to parse the YAML string: '.$e->getMessage().' in '.$e->getParsedFile());

            return;
        }
        if (!is_array($config)) {
            $this->logger->critical('Parsed config file is not an array');

            return;
        }

        $config = $this->configurator->resolveMainConfig($config);

        //trusted proxies
        if (array_key_exists('trustedProxies', $config)) {
            $arrayOfProxies = is_string($config['trustedProxies']) ? array($config['trustedProxies']) : $config['trustedProxies'];
            Request::setTrustedProxies($arrayOfProxies);
        }

        //global hook options
        if (array_key_exists('options', $config)) {
            $this->options = $this->configurator->resolveOptions($config['options']);
        }

        // global hook commands
        if (array_key_exists('commands', $config)) {
            $this->addCommand($config['commands']);
        }

        // global path
        if (array_key_exists('path', $config)) {
            $this->path = $config['path'];
        }

        // configure global repositories
        if (array_key_exists('repositories', $config)) {
            $this->handleRepositoryConfig($config);
        }

        // configure repositoriesDir
        if (array_key_exists('repositoriesDir', $config)) {
            $this->loadRepos($config['repositoriesDir']);
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
                $this->logger->error('Unable to parse the YAML string: '.$e->getMessage().' in '.$e->getParsedFile());
                continue;
            }
            if (!is_array($config)) {
                $this->logger->error('Parsed config file '.$file.' is not an array');
                continue;
            }

            $count += $this->handleRepositoryConfig($config);
        }

        return $count;
    }

    /**
     * @param string            $name
     * @param string|null       $path
     * @param array             $options
     * @param null|array|string $commands
     *
     * @return Repository
     */
    public function addRepository($name, $path = null, array $options = array(), $commands = null)
    {
        $path = $path ? $path : $this->path;

        $repository = new Repository($name, $path, $options, $this->options, $this->logger);
        $commands && $repository->addCommand($commands);

        if (!isset($this->repositoryList[$repository->getName()])) {
            $this->logger->info('Add repository '.$repository->getName().', path: '.$repository->getPath());

            $this->repositoryList[$repository->getName()] = $repository;
        }

        return $this->repositoryList[$repository->getName()];
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
     * Handle git web hook query.
     *
     * @param Event|null $event
     */
    public function execute(Event $event = null)
    {
        $event = $event ? $event : $this->createEvent();
        $this->logger->info('Starting web hook handle');

        if (!$event->isValid()) {
            $this->logger->error('Found not valid event from '.$event->getHost());
            $this->return404();

            return;
        }

        if (!$this->checkPermissions($event, $this->options)) {
            return;
        }

        $repoName = $event->getRepositoryName();
        $branchName = $event->getBranchName();
        $repository = $this->getRepository($repoName);
        if (!$repository || !($branch = $repository->getBranch($branchName))) {
            $this->logger->warning('Repository: '.$repoName.' and branch: '.$branchName.' not found in the settings');
            $this->return404();

            return;
        }

        $commandResults = $this->commandExecutor->execute(array($this, $repository, $branch));
        $this->sendEmails($event, $commandResults);

        $this->logger->info('End of web hook handle');
    }

    /**
     * @param string|array $where
     * @param string       $that
     *
     * @return bool
     */
    private function checkAllow($where, $that)
    {
        if (is_array($where) && !array_search('*', $where)) {
            $this->logger->info('Checking permissions '.$that.', in: '.var_export($where, true));

            return  in_array($that, $where) ? true : false;
        }

        if ($where == '*' || (is_array($where) && array_search('*', $where)) || (trim($where) == trim($that))) {
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
     * @param string                $recipient
     * @param string                $from
     * @param array|CommandResult[] $resultCommands
     */
    private function sendEmail(Event $event, $recipient, $from, array $resultCommands)
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

        $this->logger->info('Send email to '.$recipient.' subject '.$subject);
        $this->logger->debug('Text of email: '.$message);

        if (!mail($recipient, $subject, $message, implode($headers, "\r\n"))) {
            $this->logger->error('Cannot send email to '.$recipient);
        }
    }

    /**
     * @param Event                 $event
     * @param array|CommandResult[] $results
     */
    private function sendEmails(Event $event, array $results)
    {
        $mailParts = array();
        foreach ($results as $commandResult) {
            $options = $commandResult->getOptions();

            if (!$options['sendEmails']) {
                continue;
            }

            $mailRecipients = $options['mailRecipients'];
            if ($options['sendEmailAuthor']) {
                $mailRecipients[] = $event->getAuthor();
            }
            $mailRecipients = array_unique($mailRecipients);
            if (empty($mailRecipients) || !is_array($commandResult->getOutput()) || count($commandResult->getOutput()) == 0) {
                continue;
            }

            foreach ($mailRecipients as $recipient) {
                if (!array_key_exists($recipient, $mailParts)) {
                    $mailParts[$recipient] = array();
                }
                $mailParts[$recipient][] = $commandResult;
            }
        }

        foreach ($mailParts as $mail => $parts) {
            $this->sendEmail($event, $mail, $this->options['sendEmailFrom'], $parts);
        }
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
     * @param array $config
     *
     * @return int
     */
    private function handleRepositoryConfig($config)
    {
        $count = 0;
        if (is_array($config) && array_key_exists('repositories', $config)) {
            foreach ($config['repositories'] as $repoName => $repoConf) {
                $repoConf = $this->configurator->resolveRepositoryConfig($repoConf);
                $repository = $this->addRepository($repoName, $repoConf['path'], $repoConf['options'], $repoConf['commands']);
                ++$count;

                // adding branches
                if (array_key_exists('branch', $repoConf)) {
                    foreach ($repoConf['branch'] as $branchName => $branchOptions) {
                        $branchOptions = $this->configurator->resolveBranchConfig($branchOptions);
                        $repository->addBranch($branchName, $branchOptions['commands'], $branchOptions['path'], $branchOptions['options']);
                    }
                }
            }
        }

        return $count;
    }
}
