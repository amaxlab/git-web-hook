<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:06
 */

namespace AmaxLab\GitWebHook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Hook
 *
 * @package AmaxLab\GitWebHook
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
     * Constructor
     *
     * @param string          $path     global path
     * @param array           $options  hook options
     * @param LoggerInterface $logger   logger
     * @param Request         $request  Symfony request object
     */
    public function __construct($path = null, array $options = array(), LoggerInterface $logger = null, Request $request = null)
    {
        $this->path    = $path?$path:getcwd();
        $this->request = $request?$request:Request::createFromGlobals();
        $this->logger  = $logger?$logger:new NullLogger();
        $this->options = $this->validateOptions($options);

        $this->logger->debug('Create hook with params ' . json_encode($this->options));
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
     * @param string $dir
     *
     * @return int Count of loaded repos
     */
    public function loadRepos($dir)
    {

        $files = glob($dir.'*.php');
        $count = 0;

        foreach ($files as $file) {
            $fileReturn = include $file;

            $count += $this->handleRepoArray($fileReturn);
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
        if (!$path) {
            $path = $this->path;
        }

        $builder = new RepositoryBuilder();
        $builder
            ->setName($name)
            ->setPath($path)
            ->setOptions($options)
            ->setCommands($commands);

        return $this->registerRepository($builder->build($this));
    }

    /**
     * @param RepositoryBuilder $builder
     *
     * @return Repository
     */
    public function addRepositoryBuilder(RepositoryBuilder $builder)
    {
        return $this->registerRepository($builder->build($this));
    }

    /**
     * @param Repository $repository
     *
     * @return Repository
     */
    public function registerRepository(Repository $repository)
    {
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
        } else {
            $this->logger->info('Add hook command ' . $command);

            $command = new Command($command, $this->logger);
            $this->commandsList[] = $command;
        }

        return $this;
    }

    /**
     * Handle git web hook query
     *
     * @param Event $event
     */
    public function execute(Event $event = null)
    {
        $event = $event?$event:$this->createEvent();
        $this->logger->info('Starting web hook handle');
        $commandsResult = array();

        if (!$event->isValid()) {
            $this->logger->error('Found not valid event from ' . $event->getHost());
            $this->return404();

            return;
        }

        if (!empty($this->commandsList) && $this->checkPermissions($event, $this->options)) {
            $commandsResult['hook'] = $this->executeCommands();
            //$this->sendEmails($event, 'Hook', $commandsResult, $this->options);
        }

        $repository = $this->getRepository($event->getRepositoryName());
        if (!$repository || !($branch = $repository->getBranch($event->getBranchName())) ) {
            $this->logger->warning('Repository: ' . $event->getRepositoryName() . ' and branch: ' . $event->getBranchName() . ' not found in the settings');
            $this->return404();

            return;
        }


        if ($this->checkPermissions($event, $repository->getOptions())) {
            $commandsResult['repository'] = $repository->executeCommands($this->path);
            //$this->sendEmails($event, $repository->getName(), $commandsResult, $repository->getOptions());
        }


        if ($this->checkPermissions($event, $branch->getOptions())) {
            $commandsResult['branch'] = $branch->executeCommands($repository->getPath());
            //$this->sendEmails($event, $branch->getName() . ' (' . $repository->getName() . ')', $commandsResult, $branch->getOptions());
        }

        $this->sendEmails($event, $commandsResult);


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
        if (is_array($where)) {
            $this->logger->info('Checking permissions ' . $that . ', in: ' . var_export($where, true));

            return  in_array($that, $where) ? true : false;
        } elseif ($where == '*' || (trim($where) == trim($that))) {
            $this->logger->info('Checking permissions ' . $that . ', in: ' . $where);

            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $name
     *
     * @return Repository|null
     */
    private function getRepository($name)
    {
        return array_key_exists($name, $this->repositoryList) ? $this->repositoryList[$name] : null;
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
            $this->logger->debug('Config: '.$securityCode . ' != $_GET:' . $event->getSecurityCode());

            return false;
        }

        if (!$this->checkAllow($host, $event->getHost())) {
            $this->logger->warning('Host ' . $event->getHost() . ' not allowed on this branch');

            return false;
        }

        if (!$this->checkAllow($author, $event->getAuthor())) {
            $this->logger->warning('Author ' . $event->getAuthor() . ' not allowed on this branch');

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
            'From: ' . $from,
        );

        $subject = $event->getRepositoryName() . '('.$event->getBranchName().')';
        $hr = '<tr><td colspan="2"><hr></td></tr>';
        $message = '<html><head><title>'.$subject.'</title></head><body><table>'
                  .'<tr><td><b>Author</b></td><td>' . $event->getAuthorFull() . '</td></tr>'
                  .'<tr><td><b>Message</b></td><td>' . $event->getMessage() . '</td></tr>'
                  .'<tr><td><b>Timestamp</b></td><td>' . $event->getTimestamp() . '</td></tr>'.$hr;


        foreach ($resultCommands as $result) {
            $color =  ($result->getResultCode() == 0) ? 'green' : 'red';
            $message .= '<tr><td style="color: ' . $color . '" colspan="2"><b>Result of command ' . $result->getCommand() . ':</b></td></tr>';

            foreach ($result->getOutput() as $line) {
                $message .= '<tr><td colspan="2">' . $line . '</td></tr>';
            }

            $message .= $hr;
        }

        $message .= '</table></body></html>';

        $this->logger->info('Send email to ' . $to . ' subject ' . $subject);
        $this->logger->debug('Text of email: ' . $message);

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
        foreach ($results as $key => $resultCommands) {
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
     * @param array $options
     *
     * @return array
     */
    private function validateOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'sendEmails'            => false,
            'sendEmailAuthor'       => false,
            'sendEmailFrom'         => 'git-web-hook@'.$this->request->getHost(),
            'mailRecipients'        => array(),
            'allowedAuthors'        => array(),
            'allowedHosts'          => array(),
            'securityCode'          => '',
            'securityCodeFieldName' => 'code',
            'repositoryFieldName'   => 'url',
        ));

        return $resolver->resolve($options);
    }

    /**
     * @return Event
     */
    private function createEvent()
    {
        return new Event($this->logger, $this->request, array(
            'securityCodeFieldName' => $this->options['securityCodeFieldName'],
            'repositoryFieldName'   => $this->options['repositoryFieldName'],
        ));
    }

    /**
     * @param array|RepositoryBuilder|RepositoryBuilder[] $data
     *
     * @return int
     */
    private function handleRepoArray($data)
    {
        $count = 0;
        if (is_array($data)) {
            foreach ($data as $dataElement) {
                $count += $this->handleRepoArray($dataElement);
            }
        }
        if ($data instanceof RepositoryBuilder) {
            $this->registerRepository($data->build($this));
            $count += 1;
        }

        return $count;
    }

    /**
     * Return 404 error
     */
    private function return404()
    {
        $this->logger->warning('Returning 404 error');
        $response = new Response(null, 404);
        $response->send();
    }
}
