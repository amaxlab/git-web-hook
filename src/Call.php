<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 9:06
 */

namespace AmaxLab\GitWebHook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Psr\Log\LoggerInterface;


/**
 * Class Call
 *
 * @package AmaxLab\GitWebHook
 */
class Call
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
    protected $isValid = true;

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

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'securityCodeFieldName' => 'code',
            'repositoryFieldName'   => 'url',
        ));
        $this->options = $resolver->resolve($options);

        $this->request = $request;
        $this->logger->debug('Create call with params ' . json_encode($this->options));
        $this->logger->debug('Request server values: ' . json_encode($this->request->server));

        //TODO: Возможен запрос через прокси нужно учитывать
        $this->host = $this->request->getClientIp();
        $queryBag = $this->request->query;
        $this->securityCode = $queryBag->has('securityCodeFieldName')?$queryBag->get('securityCodeFieldName'): '';

        $body = $this->request->getContent();

        if (!$body) {
            $this->logger->error('Call content is null');
            $this->isValid = false;

            return;
        }

        $this->logger->debug('Call content: ' . $body);

        try {
            $json = json_decode($body, true);
            if (isset($json['ref'])) {
                $count = count($json['commits'])-1;
                $this->author     = $json['commits'][$count]['author']['email'];
                $this->authorName = $json['commits'][$count]['author']['name'];
                $this->message    = $json['commits'][$count]['message'];
                $this->timestamp  = $json['commits'][$count]['timestamp'];
                $this->repository = $json['repository'][$this->options['repositoryFieldName']];
                $this->branch     = substr($json['ref'], strrpos($json['ref'], '/')+1);
            } else {
                $this->isValid = false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception on decode json text');
            $this->isValid = false;
        }
    }

    /**
     * Return 404 url
     */
    public function return404()
    {
        $this->logger->warning('return 404');
        $response = new Response(null, 404);
        $response->send();
    }

    /**
     * Return 403 url
     */
    public function return403()
    {
        $this->logger->warning('return 403');
        $response = new Response(null, 403);
        $response->send();
    }

    /**
     * @return string
     */
    public function getBranch()
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
    public function getRepository()
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
        return $this->authorName . '<' . $this->author . '>';
    }

    /**
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @return boolean
     */
    public function isIsValid()
    {
        return $this->isValid;
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
}
