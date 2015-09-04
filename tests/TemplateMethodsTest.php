<?php

/**
 * Created by PhpStorm.
 * User: ibodnar
 * Date: 23.08.15
 * Time: 12:23.
 */
namespace AmaxLab\GitWebHook\Tests;

use AmaxLab\GitWebHook\Template;

/**
 * Class TemplateMethodsTestTest.
 */
class TemplateMethodsTest extends GWHTestCase
{
    /**
     * Test render method.
     */
    public function testRender()
    {
        $templateDir = $this->makeTempDir('templates');
        $filename = 'test-template.php';
        $template = $templateDir.DIRECTORY_SEPARATOR.$filename;
        $this->fs->touch($template);
        file_put_contents($template, '123**<?php echo $this->someParameter ?>');

        $template = new Template($templateDir);
        $content = $template->render($filename, array(
            'someParameter' => 321,
        ));
        $this->assertEquals('123**321', $content, sprintf('Failed to render template, expected %s found %s', '123**321', $content));
    }
}
