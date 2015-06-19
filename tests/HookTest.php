<?php
/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 18.06.15
 * Time: 17:10
 */

namespace AmaxLab\GitWebHook\Tests;

use AmaxLab\GitWebHook\Hook;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class HookTest extends GWHTestCase
{

    public function testExecute()
    {
        $repoUrl = 'git@github.com:amaxlab/git-web-hook-test.git';
        $json = '{"ref":"refs/heads/master","repository":{"name":"git-web-hook-test","url":"'.$repoUrl.'"},"commits":[{"id":"06f9ce8478e0973ec17b6253000a1f1f140c322b","message":"test commit1","timestamp":"2014-12-25T15:20:16+06:00","author":{"name":"Egor Zyuskin","email":"egor@zyuskin.ru"}}]}';

        $options = array(
            'sendEmails'          => false,
            'sendEmailAuthor'     => false,
            'mailRecipients'      => array(),
            'allowedAuthors'      => '*',
            'allowedHosts'        => '*',
        );

        $tempDir  = sys_get_temp_dir().'/test_GWH/';
        $repoDir = $tempDir.'repo/';
        $logFile = $tempDir.'hook.log';
        $this->markDirToBeRemoved($repoDir);
        $this->markDirToBeRemoved($tempDir);

        $fs = new Filesystem();

        try {
            $fs->remove($tempDir);
        } catch (IOExceptionInterface $e) {
        }

        try {
            $fs->mkdir($repoDir);
        } catch (IOExceptionInterface $e) {
            $this->fail(sprintf('Can\'t create directory %s', $repoDir));
        }

        try {
            $fs->touch($logFile);
        } catch (IOExceptionInterface $e) {
            $this->fail('Can\'t create logfile');
        }


        $this->assertTrue(chdir($repoDir), sprintf('Can\'t change directory to %s', $repoDir));
        $result = shell_exec('git init');
        $this->assertTrue(is_dir($repoDir.'/.git/'), sprintf('Can\'t init git repository in %s : %s', $repoDir, $result), true);
        $this->assertEmpty(shell_exec('git remote add origin '.$repoUrl), 'Can\'t add origin');

        $logger = new Logger('git-web-hook');
        $logger->pushHandler(new StreamHandler($logFile, Logger::WARNING));

        $requestMock = $this->getMock('Symfony\\Component\\HttpFoundation\\Request', null, array($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER, $json));
        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Request', $requestMock, 'Error during create mock Request');

        $hook = new Hook($repoDir, $options, $logger, $requestMock);

        $hook->addRepository($repoUrl)
            ->addBranch('master')
                ->addCommand(array(
                    'git status',
                    'git pull origin master',
                ));


        $hook->execute();
        $content = file_get_contents($logFile);
        $this->assertEmpty($content, sprintf('Log file is not empty: %s', $content));
    }
}
