<?php

/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 0:42.
 */
namespace AmaxLab\GitWebHook;

use Psr\Log\LoggerInterface;

/**
 * Class Branch.
 */
class Branch extends BaseCommandContainer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Repository $repository     Repository owen this branch
     * @param string     $name           Name of branch
     * @param string     $path           path for root directory of repository
     * @param array      $options        options
     * @param array      $defaultOptions Options passed from repository
     */
    public function __construct(Repository $repository, $name, $path, array $options = array(), array $defaultOptions = array())
    {
        parent::__construct($name, $path, $options, $defaultOptions);

        $this->logger = $repository->getLogger();
        $this->logger->debug('Create branch with params '.json_encode($this->options));
    }
}
