<?php

/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 9:06.
 */
namespace AmaxLab\GitWebHook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Psr\Log\LoggerInterface;

/**
 * Class Event.
 */
class Event
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $author;

    /**
     * @var string
     */
    protected $authorName;

    /**
     * @var string
     */
    protected $repository;

    /**
     * @var string
     */
    protected $branch;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $timestamp;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $securityCode;

    /**
     * @var bool
     */
    protected $valid = true;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param LoggerInterface $logger
     * @param Request         $request
     * @param array           $options
     */
    public function __construct(LoggerInterface $logger, Request $request, array $options = array())
    {
        $this->logger = $logger;

        $this->options = $this->configureOptions($options);

        $this->request = $request;
        $this->logger->debug('Create call with params '.json_encode($this->options));
        $this->logger->debug('Request server values: '.json_encode($this->request->server));

        $this->host = $this->request->getClientIp();
        $queryBag = $this->request->query;
        $this->securityCode = $queryBag->has('securityCodeFieldName') ? $queryBag->get('securityCodeFieldName') : '';

        $body = $this->request->getContent();

        if (!$body) {
            $this->logger->error('Event content is null');
            $this->valid = false;

            return;
        }

        $this->logger->debug('Event content: '.$body);

        try {
            $json = json_decode($body, true);
        } catch (\Exception $e) {
            $this->logger->error('Exception on decode json text');
            $this->valid = false;
        }

        if (!isset($json['ref'])) {
            $this->valid = false;

            return;
        }

        $count = count($json['commits']) - 1;
        $this->author = $json['commits'][$count]['author']['email'];
        $this->authorName = $json['commits'][$count]['author']['name'];
        $this->message = $json['commits'][$count]['message'];
        $this->timestamp = $json['commits'][$count]['timestamp'];
        $this->repository = $json['repository'][$this->options['repositoryFieldName']];
        $this->branch = substr($json['ref'], strrpos($json['ref'], '/') + 1);
    }

    /**
     * @return string
     */
    public function getBranchName()
    {
        return $this->branch;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getRepositoryName()
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getAuthorFull()
    {
        return $this->authorName.'<'.$this->author.'>';
    }

    /**
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return string
     */
    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function configureOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'securityCodeFieldName' => 'code',
            'repositoryFieldName' => 'url',
        ));

        return $resolver->resolve($options);
    }
}
