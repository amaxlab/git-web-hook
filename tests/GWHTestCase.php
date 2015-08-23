<?php
/**
 * Created by PhpStorm.
 * User: Igor Bodnar <bodnar_i@mail.ru>
 * Date: 18.06.2015
 * Time: 21:40
 */

namespace AmaxLab\GitWebHook\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class GWHTestCase added some useful methods in PHPUnit_Framework_TestCase
 *
 * @package AmaxLab\GitWebHook\Tests
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

    protected function setUp()
    {
        parent::setUp();

        $this->fs = new Filesystem();
        $this->baseTempDir  = sys_get_temp_dir().'/test_GWH/';
        try {
            $this->fs->remove($this->baseTempDir);
        } catch (IOExceptionInterface $e) {
        }
        try {
            $this->fs->mkdir($this->baseTempDir);
        } catch (IOExceptionInterface $e) {
            $this->fail(sprintf('Can\'t create directory %s', $this->baseTempDir));
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
     * Mark directory to be removed
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
     * Overrided tearDown
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
}
