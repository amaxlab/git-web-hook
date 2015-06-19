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
     * Recursively remove directory and all files inside it
     *
     * @param string $dir
     *
     * @return bool
     */
    public function removeDir($dir)
    {
        if (!is_dir($dir) && !is_file($dir)) {
            return true;
        }
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);

        return true;
    }

    /**
     * Mark directory to be removed
     *
     * @param $dir
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