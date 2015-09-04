repositories:
<?php for ($i = 0; $i < $this->count; ++$i) :?>
    git@github.com:amaxlab/git-web-hook-test<?php echo $i; ?>.git:
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
<?php
endfor;
echo "\r\n";
