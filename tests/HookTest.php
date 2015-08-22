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
            ->addBranch('master', array(
                'git status',
                'git pull origin master',
            ));


        $hook->execute();
        $content = file_get_contents($logFile);
        $this->assertEmpty($content, sprintf('Log file is not empty: %s', $content));
    }

    public function testLoadRepos()
    {
        $options = array(
            'sendEmails'          => false,
            'sendEmailAuthor'     => false,
            'mailRecipients'      => array(),
            'allowedAuthors'      => '*',
            'allowedHosts'        => '*',
        );
        $hook = new Hook(__DIR__, $options);

        $fs = new Filesystem();
        $reposDir = sys_get_temp_dir().'/test_GWH/repos.d/';
        $this->markDirToBeRemoved($reposDir);
        try {
            $fs->mkdir($reposDir);
        } catch (IOExceptionInterface $e) {
            $this->fail(sprintf('Can\'t create directory %s', $reposDir));
        }

        $testFile1 = $reposDir.'test1.yml';
        $testFile2 = $reposDir.'test2.yml';
        try {
            $fs->touch(array($testFile1, $testFile2));
        } catch (IOExceptionInterface $e) {
            $this->fail('Can\'t create logfile');
        }

        $count = $hook->loadRepos($testFile1);
        $this->assertEquals(0, $count, 'Wrong number of loaded repositories');

        for ($i = 1; $i <=3; $i++) {
            $this->generateBuilderFile($testFile1, $i);
            $count = $hook->loadRepos($reposDir);
            $this->assertEquals($i, $count, sprintf('Wrong number of loaded repositories, found %s, expected %s', $count, $i));
        }

        $this->generateBuilderFile($testFile2, 2);
        $count = $hook->loadRepos($reposDir);
        $this->assertEquals(5, $count, sprintf('Wrong number of loaded repositories, found %s, expected %s', $count, 5));
    }

    /**
     * @param string $file
     * @param int    $countOfBuilders
     */
    private function generateBuilderFile($file, $countOfBuilders)
    {
        $output = "repositories:\r\n";


        for ($i = 1; $i <= $countOfBuilders; $i++) {
            $output .= '    git@github.com:amaxlab/git-web-hook-test'.$i.'.git:
        path: null
        options: {}
        commands:
          - git status
        branch:
            master:
                path: null
                options: {}
                commands:
                  - git reset --hard HEAD
                  - git pull origin master
            production:
                commands:
                  - git reset --hard HEAD
                  - git pull origin production
';
        }

        file_put_contents($file, $output);
    }
}
