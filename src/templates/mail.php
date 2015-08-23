<?php
/**
 * Mail template
 */
use AmaxLab\GitWebHook\CommandResult;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo $this->subject?></title>
    </head>
    <body>
        <table>
            <tr>
                <td><span style="font-weight: bold;">Author</span></td>
                <td><?php echo $this->event->getAuthorFull()?></td>
            </tr>
            <tr>
                <td><span style="font-weight: bold;">Message</span></td>
                <td><?php echo $this->event->getMessage()?></td>
            </tr>
            <tr>
                <td><span style="font-weight: bold;">Timestamp</span></td>
                <td><?php echo $this->event->getTimestamp()?></td>
            </tr>
            <tr>
                <td colspan="2"><hr></td>
            </tr>

            <?php
            /** @var CommandResult $result */
            foreach ($this->resultCommands as $result) :
                $color = ($result->getResultCode() == 0) ? 'green' : 'red'; ?>
                    <tr>
                        <td style="color: <?php echo $color ?>" colspan="2">
                            <span style="font-weight: bold;">Result of command <?php echo $result->getCommand() ?>:</span>
                        </td>
                    </tr>';
                <?php
                foreach ($result->getOutput() as $line) {
                    echo '<tr><td colspan="2">'.$line.'</td></tr>';
                }
                ?>
                    <tr><td colspan="2"><hr></td></tr>
            <?php
            endforeach; ?>
        </table>
    </body>
</html>
