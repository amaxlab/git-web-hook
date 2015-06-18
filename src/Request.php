<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 9:06
 */

namespace AmaxLab\GitWebHook;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Psr\Log\LoggerInterface;


/**
 * Class Request
 *
 * @package AmaxLab\GitWebHook
 */
class Request
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
     * @param LoggerInterface $logger
     * @param array           $options
     */
    public function __construct(LoggerInterface $logger, array $options = array())
    {
        $this->logger = $logger;

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'securityCodeFieldName' => 'code',
            'repositoryFieldName'   => 'url',
        ));
        $this->options = $resolver->resolve($options);

        $this->logger->debug('Create request with params ' . json_encode($this->options));
        $this->logger->debug('Request server values: ' . json_encode($_SERVER));

        //TODO: Возможен запрос через прокси нужно учитывать
        $this->host = $_SERVER['REMOTE_ADDR'];
        $this->securityCode = (isset($_GET[$this->options['securityCodeFieldName']])) ? trim(htmlspecialchars($_GET[$this->options['securityCodeFieldName']]), ENT_QUOTES) : "";

        $json = trim(@file_get_contents('php://input'));

        if (!$json) {
            $this->logger->error('Request content is null');
            $this->isValid = false;

            return;
        }

        $this->logger->debug('Request content: ' . $json);

        try {
            $json = json_decode($json, true);
            if (isset($json['ref'])) {
                $this->author     = $json['commits'][count($json['commits'])-1]['author']['email'];
                $this->authorName = $json['commits'][count($json['commits'])-1]['author']['name'];
                $this->message    = $json['commits'][count($json['commits'])-1]['message'];
                $this->timestamp  = $json['commits'][count($json['commits'])-1]['timestamp'];
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
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    }

    /**
     * Return 403 url
     */
    public function return403()
    {
        $this->logger->warning('return 403');
        header($_SERVER["SERVER_PROTOCOL"]." 403 Access denied");
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