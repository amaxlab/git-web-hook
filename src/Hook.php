<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:06
 */

namespace AmaxLab\GitWebHook;

use HttpException;
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
     * @var Request
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array|Command[]
     */
    protected $commandsList = array();

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
     * Constructor
     *
     * @param LoggerInterface $logger logger
     * @param string          $path global path
     * @param array           $options hook options
     * @throws HttpException
     */
    public function __construct($path = '', array $options = array(), LoggerInterface $logger = null)
    {
        if (!$logger) {
            $logger = new NullLogger();
        }

        if (!$path) {
            $path = getcwd();
        }
        $this->path = $path;

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'sendEmails'            => false,
            'sendEmailAuthor'       => false,
            'sendEmailFrom'         => 'git-web-hook@'.gethostname(),
            'mailRecipients'        => array(),
            'allowedAuthors'        => array(),
            'allowedHosts'          => array(),
            'securityCode'          => '',
            'securityCodeFieldName' => 'code',
            'repositoryFieldName'   => 'url',
        ));
        $this->options = $resolver->resolve($options);

        $this->logger = $logger;

        $this->logger->debug('Create hook with params ' . json_encode($this->options));

        $this->request = new Request($this->logger, array('securityCodeFieldName' => $this->options['securityCodeFieldName'], 'repositoryFieldName' => $this->options['repositoryFieldName']));
    }

    /**
     * @param string $name
     * @param string $path
     * @param array  $options
     *
     * @return Repository
     */
    public function addRepository($name, $path = '', array $options = array())
    {
        if (!$path) {
            $path = $this->path;
        }

        if (!isset($this->repositoryList[$name])) {
            $this->logger->info('Add repository ' . $name . ', path: ' . $path);

            $this->repositoryList[$name] = new Repository($this, $name, $path, $options);
        }

        return $this->repositoryList[$name];
    }

    /**
     * @param string $name
     *
     * @return bool|Repository
     */
    private function getRepository($name)
    {
        return isset($this->repositoryList[$name]) ? $this->repositoryList[$name] : false;
    }

    /**
     * @return int
     */
    private function getRepositoryCount()
    {
        return count($this->repositoryList);
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
            $this->logger->info('Add hook command ' . $command . ', path: ' . $path);

            $command = new Command($command, $path, $this->logger);
            $this->commandsList[] = $command;
        }

        return $this;
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
            $this->logger->info('Checking permissions ' . $that . ', in: ' . print_r($where));

            return  in_array($that, $where) ? true : false;
        } elseif ($where == '*' or (trim($where) == trim($that))) {
            $this->logger->info('Checking permissions ' . $that . ', in: ' . $where);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string       $securityCode
     * @param string|array $author
     * @param string|array $host
     *
     * @return bool
     */
    private function checkPermissions($securityCode, $author, $host)
    {
        if ($securityCode && $securityCode != $this->request->getSecurityCode()) {
            $this->logger->warning('Security code not match');
            $this->logger->debug('Config: '.$securityCode . ' != $_GET:' . $this->request->getSecurityCode());

            return false;
        }

        if (!$this->checkAllow($host, $this->request->getHost())) {
            $this->logger->warning('Host ' . $this->request->getHost() . ' not allowed on this branch');

            return false;
        }

        if (!$this->checkAllow($author, $this->request->getAuthor())) {
            $this->logger->warning('Author ' . $this->request->getAuthor() . ' not allowed on this branch');

            return false;
        }

        return true;
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $from
     * @param array  $resultCommands
     */
    private function sendEmail($to, $subject, $from, $resultCommands)
    {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $from,
        );

        $hr = '<tr><td colspan="2"><hr></td></tr>';
        $message = '<html><head><title>'.$subject.'</title></head><body><table>'
                  .'<tr><td><b>Author</b></td><td>' . $this->request->getAuthorFull() . '</td></tr>'
                  .'<tr><td><b>Message</b></td><td>' . $this->request->getMessage() . '</td></tr>'
                  .'<tr><td><b>Timestamp</b></td><td>' . $this->request->getTimestamp() . '</td></tr>'.$hr;


        foreach ($resultCommands as $result) {
            $color =  ($result['errorCode'] == 0) ? 'green' : 'red';
            $message .= '<tr><td style="color: ' . $color . '" colspan="2"><b>Result of command ' . $result['command'] . ':</b></td></tr>';
            if (count($result['output']) > 0) {
                foreach ($result['output'] as $line) {
                    $message .= '<tr><td colspan="2">' . $line . '</td></tr>';
                }
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
     * @param string $subject
     * @param array  $resultCommands
     * @param array  $options
     */
    private function sendEmails($subject, $resultCommands, $options)
    {
        if (!$options['sendEmails']) {
            return;
        }

        $mailRecipients  = $options['mailRecipients'];
        if ($options['sendEmailAuthor']) {
            $mailRecipients[] = $this->request->getAuthor();
        }
        $mailRecipients = array_unique($mailRecipients);

        if (empty($mailRecipients) || empty($resultCommands)) {
            return;
        }

        foreach ($mailRecipients as $email) {
            if ($email) {
                $this->sendEmail($email, $subject, $options['sendEmailFrom'], $resultCommands);
            }
        }
    }

    /**
     * @return array
     */
    private function executeCommands()
    {
        $this->logger->info('Execute commands for hook ...');
        $result = array();
        if (!empty($this->commandsList)) {
            foreach ($this->commandsList as $command) {
                $result[] = $command->execute();
            }
        }

        return $result;
    }

    /**
     * Handle git web hook query
     */
    public function execute()
    {
        $this->logger->info('Starting web hook handle');

        if (!$this->request->isIsValid()) {
            $this->logger->error('Request from ' . $this->request->getHost() . ' not valid');

            $this->request->return404();

            return;
        }

        if (!empty($this->commandsList) && $this->checkPermissions($this->options['securityCode'], $this->options['allowedAuthors'], $this->options['allowedHosts'])) {
            $commandsResult = $this->executeCommands();
            $this->sendEmails('Hook', $commandsResult, $this->options);
        }

        if ($repository = $this->getRepository($this->request->getRepository())) {
            $options = $repository->getOptions();
            if ($repository->getCommandsCount() > 0 && $this->checkPermissions($options['securityCode'], $options['allowedAuthors'], $options['allowedHosts'])) {
                $commandsResult = $repository->executeCommands();
                $this->sendEmails($repository->getName(), $commandsResult, $options);
            }

            if ($branch = $repository->addBranch($this->request->getBranch())) {
                $options = $branch->getOptions();
                if ($branch->getCommandsCount() > 0 && $this->checkPermissions($options['securityCode'], $options['allowedAuthors'], $options['allowedHosts'])) {
                    $commandsResult = $branch->executeCommands();
                    $this->sendEmails($branch->getName() . ' (' . $repository->getName() . ')', $commandsResult, $options);
                }
            }
        } else {
            // Disable warning for global hook
            if (!empty($this->commandsList) && $this->getRepositoryCount() == 0) {
                $this->logger->warning('Repository: ' . $this->request->getRepository() . ' and branch: ' . $this->request->getBranch() . ' not found in the settings');
            }
        }
        $this->logger->info('End of web hook handle');
    }
}
