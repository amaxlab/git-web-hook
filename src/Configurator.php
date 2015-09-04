<?php

/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 04.09.15
 * Time: 23:29.
 */
namespace AmaxLab\GitWebHook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Class Configurator.
 */
class Configurator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param Request                  $request
     */
    public function __construct(LoggerInterface $logger, Request $request)
    {
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * @param array $mainConfig
     *
     * @return array
     */
    public function resolveMainConfig(array $mainConfig)
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
            ->setAllowedTypes('trustedProxies', array('array', 'string'))
            ->setAllowedTypes('repositoriesDir', 'string')
            ->setAllowedTypes('commands', 'array')
            ->setAllowedTypes('options', 'array')
            ->setAllowedTypes('repositories', 'array')
            ->setAllowedTypes('path', array('string', 'null'))
        ;

        try {
            return $resolver->resolve($mainConfig);
        } catch (InvalidArgumentException $exception) {
            $this->logger->critical('Couldn\'t resolve main configuration file: '.$exception->getTraceAsString());
            throw $exception;
        }
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function resolveOptions(array $options)
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
     * @param array $repositoryConfig
     *
     * @return array
     */
    public function resolveRepositoryConfig(array $repositoryConfig)
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

        try {
            return $resolver->resolve($repositoryConfig);
        } catch (InvalidArgumentException $exception) {
            $this->logger->critical('Couldn\'t resolve repository config: '.$exception->getTraceAsString());
            throw $exception;
        }
    }

    /**
     * @param array $branchConfig
     *
     * @return array
     */
    public function resolveBranchConfig(array $branchConfig)
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

        try {
            return $resolver->resolve($branchConfig);
        } catch (InvalidArgumentException $exception) {
            $this->logger->critical('Couldn\'t resolve branch config: '.$exception->getTraceAsString());
            throw $exception;
        }
    }
}
