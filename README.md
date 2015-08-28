Git web hook
===

Library for handle git web hooks from gitlab.com or github.com and run commands 

Features
--------

- Run commands by global get request
- Run commands by git repository push
- Run commands by some branch push
- Security check commit author (global, repository, branch)
- Security check param from $_GET request
- Send email author and mail recipients with the results of the execute command

Require
-------

- php >= 5.3
- symfony/options-resolver ^2.3
- symfony/yaml ^2.3
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

### Old way:
Specify config in php file directly:

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
$hook
	->addRepository('git@github.com:amaxlab/git-web-hook.git', '/var/www/my_project_folder/web', array(/*command executed on each push to repository*/))
		->addBranch('master', array('git status', 'git reset --hard HEAD', 'git pull origin master'), '/var/www/my_project_folder/demo_subdomain',  array(/* array of redefined options*/)) // commands executed on push to specified branch in /var/www/html/my_site/ folder
 		->addBranch('production', 'git pull origin production');

$hook->execute();
```

You can also specify some commands to execute them on hook call:

```php
$hook->addCommand($someCommand);
```

### Preferred way:

Load config from yaml file
```php
<?php

include __DIR__.'/../vendor/autoload.php';

use AmaxLab\GitWebHook\Hook;

$hook = new Hook(__DIR__);
$hook->loadConfig('/var/www/ghw/config.yml');
$hook->execute();
```


Configuration
-------------

Configuration can be unique for each branch, it is enough to pass the variable options of type array. You can pass them directly while creating hook or load through main config.yml file. You should use yaml files, due to configuration through php will be removed in future releases.
See examples below.

```php
$options = array(
    'sendEmails'            => false,                          // Enable or disable sending emails
    'sendEmailAuthor'       => false,                          // Enable or disable sending email commit author
    'sendEmailFrom'         => 'git-web-hook@'.gethostname(),  // Email address from which messages are sent
    'mailRecipients'        => array(),                        // Array of subscribers
    'allowedAuthors'        => array(),                        // Array of commit authors allowed to execute commands
    'allowedHosts'          => array(),                        // Array of hook hosts allowed to execute commands
    'securityCode'          => '',                             // Security code on check $_GET request
    'securityCodeFieldName' => 'code',                         // $_GET field name of security code
    'repositoryFieldName'   => 'url',                          // Repository filed name on the JSON query
);
```

```yaml
    #/var/www/ghw/config.yml
    options:
        sendEmails: false,                       # Enable or disable sending emails
        sendEmailAuthor: false,                  # Enable or disable sending email commit author
        sendEmailFrom: 'git-web-hook@youdomain', # Email address from which messages are sent
        mailRecipients: [],                      # Array of subscribers
        allowedAuthors: [],                      # Array of commit authors allowed to execute commands
        allowedHosts: [],                        # Array of hook hosts allowed to execute commands
        securityCode: '',                        # Security code on check $_GET request
        securityCodeFieldName: 'code',           # $_GET field name of security code
        repositoryFieldName: 'url',              # Repository filed name on the JSON query
    commands: [] #commands to execute on each hook call
    path: '/var/www/projects' #main path where commands will be executed, can be overwrite in repository or branch
    #trustedProxies: [192.168.0.100] # if your projects lives behind proxy you should specify it ip, to correctly determine real ip address
    repositoriesDir: /var/www/ghw/repos.d/ #directory to load additional yaml files with repository configuraton
    #repositories: # you can specify some repository directly in main config file
    #    git@github.com:amaxlab/git-web-hook-test.git:
    #        path: null
    #        options: {}
    #        commands: 
    #          - git status
    #        branch:
    #            master:
    #                path: null
    #                options: {}
    #                commands: 
    #                  - git reset --hard HEAD
    #                  - git pull origin master
    #            production:
    #                commands: 
    #                  - git reset --hard HEAD
    #                  - git pull origin production
```

Logging
-------

Use loggers [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) standard ([Monolog](https://github.com/Seldaek/monolog))

```php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

...

$logger = new Logger('git-web-hook');
$logger->pushHandler(new StreamHandler(__DIR__ . '/hook.log', Logger::WARNING));

...

$hook = new Hook(__DIR__, $options, $logger);

```

Load repository configuration
-----------------------------

If you have a lot of repositories you can place them in separate *.yml files and load all configuration from a directory:

```php
<?php

$hook = new Hook(__DIR__, $options);
$hook->loadRepos('/path/to/derectory/'); // or $hook->loadConfig('/path/to/file); if you specify `repositoriesDir` in main config.yml
$hook->execute();
```

Example of partial configuration file:
```yml

repositories:
    #one or several repositories can be described in a file
    git@github.com:amaxlab/git-web-hook-test.git:
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

```

Security code checking configuration
------------------------------------


Security code can be cofigured only on the root options.

Setup config:
```php
$options = array(
    ...
    'securityCode'          => 'GjnfkrjdsqKfvgjcjc',
    'securityCodeFieldName' => 'mySecurityCode',
    ...
);
```

```yaml
options:
    securityCode: 'GjnfkrjdsqKfvgjcjc',
    securityCodeFieldName: 'mySecurityCode',
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

TODO
----

* Remove old way of configuration (through php file)
* Resolve passing config while loading config and remove unnessesary dependencies between classes hook/repository/branch/command
* Add more tests

License
--------
This library is under the MIT license. See the complete license in [here](https://github.com/amaxlab/git-web-hook/blob/master/LICENSE)
