options:
    sendEmails: false
    sendEmailAuthor: false
    allowedAuthors: \'*\'
    allowedHosts: \'*\'
trustedProxies: [192.168.0.2, 192.168.0.3]
repositoriesDir: <?php echo $this->reposDir."\r\n"; ?>
repositories:
    git@github.com:amaxlab/git-web-hook-test.git:
        path: null
        options: {}
        commands:
          - git status
        branch:
            master:
                path: null
                options: { mailRecipients: [ test@test.test ] }
                commands:
                  - git reset --hard HEAD
                  - git pull origin master
            production:
                commands:
                  - git reset --hard HEAD
                  - git pull origin production
