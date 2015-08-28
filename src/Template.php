<?php

/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 23.08.15
 * Time: 11:46.
 */
namespace AmaxLab\GitWebHook;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Template.
 */
class Template
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $scriptPath;

    /**
     * @var array
     */
    private $properties;

    /**
     * @param string          $scriptPath
     * @param LoggerInterface $logger
     */
    public function __construct($scriptPath = null, LoggerInterface $logger = null)
    {
        $this->logger = $logger ? $logger : new NullLogger();
        $this->scriptPath = $scriptPath ? $scriptPath : __DIR__.'/templates/';
        $this->properties = array();
    }

    /**
     * @param string $scriptPath
     */
    public function setScriptPath($scriptPath)
    {
        $this->scriptPath = $scriptPath;
    }

    /**
     * @param string $filename
     * @param array  $params
     *
     * @return string
     */
    public function render($filename, array $params = array())
    {
        $this->properties = $params;

        $file = $this->scriptPath.DIRECTORY_SEPARATOR.$filename;
        if (!file_exists($file)) {
            $this->logger->error('Could not find template '.$file);

            return '';
        }
        ob_start();
        include $file;

        return ob_get_clean();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->properties[$key];
    }
}
