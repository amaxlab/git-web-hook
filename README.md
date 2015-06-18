Git web hook
===

Library for handle git web hooks from gitlab.com or github.com and run commands 

Features
--------

- Run commands by global get request
- Run commands by hook on git repository
- Run commands by hook on some branch
- Security check commit author (global, repository, branch)
- Security check param from $_GET request
- Send email author and mail recipients with the results of the execute command

Require
-------

- php >= 5.3
- symfony/options-resolver >= ~2.3
- psr/log >= ~1.0

Install
-------
``` bash
$ php composer require amaxlab/git-web-hook "~1.0"
```

or project

``` bash
$ php composer create-project amaxlab/git-web-hook-composer-install ./git-web-hook --prefer-dist
```

Usage
-----
```php
<?php

include __DIR__.'/../vendor/autoload.php';

use AmaxLab\GitWebHook\Hook;

$options = array(
    'sendEmails'          => true,
    'sendEmailAuthor'     => true,
    'mailRecipients'      => array(),
    'allowedAuthors'      => '*',
    'allowedHosts'        => '*',
);

$hook = new Hook(__DIR__, $options);

$hook->addRepository('git@github.com:amaxlab/git-web-hook.git')
     ->addBranch('master')
     ->addCommand(array('git status', 'git reset --hard HEAD', 'git pull origin master'))
     ->getParent()
     ->addBranch('production')
     ->addCommand('git pull origin production');

$hook->execute();
```

Configuration
-------------

Configuration can be unique for each branch, it is enough to pass the variable options of type array. See example below.

```php
$options = array(
    'sendEmails'            => false,                          // Enable or disable sending emails
    'sendEmailAuthor'       => false,                          // Enable or disable sending email commit author
    'sendEmailFrom'         => 'git-web-hook@'.gethostname(),  // Email address from which messages are sent
    'mailRecipients'        => array(),                        // Array subscriber 
    'allowedAuthors'        => array(),                        // Array authors email allowed on this branch
    'allowedHosts'          => array(),                        // Array hosts allowed on this branch
    'securityCode'          => '',                             // Security code on check $_GET request
    'securityCodeFieldName' => 'code',                         // $_GET field name of security code
    'repositoryFieldName'   => 'url',                          // Repository filed name on the JSON query
);
```

Logging
-------

Use loggers [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) standard ([Monolg](https://github.com/Seldaek/monolog))

```php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

...

$logger = new Logger('git-web-hook');
$logger->pushHandler(new StreamHandler(__DIR__ . '/hook.log', Logger::WARNING));

...

$hook = new Hook(__DIR__, $options, $logger);

```

Security code checking configuration
------------------------------------

Setup config:
```php
$options = array(
    ...
    'securityCode'          => 'GjnfkrjdsqKfvgjcjc',
    'securityCodeFieldName' => 'mySecurityCode',
    ...
);
```

and setup web hook on gitlab.com or github.com on 
```
http://yourhost/hook.php?mySecurityCode=GjnfkrjdsqKfvgjcjc
```

if security code not pass check the you see 
```
Jan 01 00:00:00 WARN Security code not match
```
in the log file

License
--------
This library is under the MIT license. See the complete license in [here](https://github.com/amaxlab/git-web-hook/blob/master/LICENSE)
