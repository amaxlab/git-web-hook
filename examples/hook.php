<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 31.12.14
 * Time: 1:06
 */

include __DIR__.'/../vendor/autoload.php';

use AmaxLab\GitWebHook\Hook;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$options = array(
    'sendEmails'          => true,
    'sendEmailAuthor'     => true,
    'mailRecipients'      => array(),
    'allowedAuthors'      => '*',
    'allowedHosts'        => '*',
);

$logger = new Logger('git-web-hook');
$logger->pushHandler(new StreamHandler(__DIR__ . '/hook.log', Logger::WARNING));

$hook = new Hook(__DIR__, $options, $logger);

$hook->addRepository('git@github.com:amaxlab/git-web-hook.git')
     ->addBranch('master')
     ->addCommand(array('git status', 'git reset --hard HEAD', 'git pull origin master'))
     ->getParent()
     ->addBranch('production')
     ->addCommand('git pull origin production');

$hook->execute();