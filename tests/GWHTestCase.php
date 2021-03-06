<?php

/**
 * Created by PhpStorm.
 * User: Igor Bodnar <bodnar_i@mail.ru>
 * Date: 18.06.2015
 * Time: 21:40.
 */
namespace AmaxLab\GitWebHook\Tests;

use AmaxLab\GitWebHook\Template;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class GWHTestCase added some useful methods in PHPUnit_Framework_TestCase.
 */
class GWHTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $dirsToRemove;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $baseTempDir;

    /**
     * @var Template
     */
    protected $configTemplates;

    /**
     * Sets up test case.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->fs = new Filesystem();
        $this->baseTempDir = sys_get_temp_dir().'/test_GWH/';
        try {
            $this->fs->remove($this->baseTempDir);
        } catch (IOExceptionInterface $e) {
        }
        try {
            $this->fs->mkdir($this->baseTempDir);
        } catch (IOExceptionInterface $e) {
            $this->fail(sprintf('Can\'t create directory %s', $this->baseTempDir));
        }
        $this->configTemplates = new Template(__DIR__.DIRECTORY_SEPARATOR.'configs');
    }

    /**
     * Overrided tearDown.
     */
    protected function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        if (!is_array($this->dirsToRemove)) {
            return;
        }

        try {
            $fs->chmod($this->dirsToRemove, 0777, 0000, true);
            $fs->remove($this->dirsToRemove);
        } catch (IOException $e) {
        }
    }

    /**
     * @param string $dirName
     *
     * @return string
     */
    public function makeTempDir($dirName)
    {
        $dir = $this->baseTempDir.$dirName;
        $this->markDirToBeRemoved($dir);

        try {
            $this->fs->mkdir($dir);
        } catch (IOExceptionInterface $e) {
            $this->fail(sprintf('Can\'t create directory %s', $dir));
        }

        return $dir;
    }

    /**
     * @param string $dirName
     * @param string $fileName
     *
     * @return string
     */
    public function makeFile($dirName, $fileName)
    {
        $path = $dirName.DIRECTORY_SEPARATOR.$fileName;
        $this->markDirToBeRemoved($path);

        try {
            $this->fs->touch($path);
        } catch (IOExceptionInterface $e) {
            $this->fail(sprintf('Can\'t create file %s', $path));
        }

        return $path;
    }

    /**
     * Mark directory to be removed.
     *
     * @param string|array $dir
     */
    public function markDirToBeRemoved($dir)
    {
        if (!is_array($this->dirsToRemove)) {
            $this->dirsToRemove = array();
        }
        $this->dirsToRemove[] = $dir;
    }

    /**
     * Generates some configurations files for testing purposes.
     *
     * @param string $file
     * @param int    $countOfBuilders
     */
    protected function generateRepoConfigFile($file, $countOfBuilders)
    {
        file_put_contents($file, $this->configTemplates->render('config2.php', array('count' => $countOfBuilders)));
    }
}
