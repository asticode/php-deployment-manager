# Prerequisites

You must have PHP, GIT and Composer installed on your system

You must create a UTF-8 database and a user with read/write privileges on it beforehand

# Installation

Install the project with

    $ composer create-project asticode/php-deployment-manager <your path>
    
And follow the instructions on the screen

    $ To install the manager, you need a valid UTF-8 database as well as a user with read/write privileges on it. Once you have it, please fill in the information below:
    $
    $ database host [localhost]:
    $ database name [deployment]:
    $ database user name:
    $ database user password:
    $ number of backups kept per project [2]:
    $ full path to composer binary [/usr/local/bin/composer]: 
    $ full path to git binary [/usr/bin/git]: 
    $ full path to php binary [/usr/bin/php]: 
    $ 
    $ Update local config parameters: OK
    $ 
    $ Execute SQL commands: OK
    $ 
    $ Create directories: OK
    $
    $
    $ Installation successful!

In case of a problem, remove the folder created and re-run the previous command.
    
You can add a new project anytime with

    $ <your path>/app/console project:add
    
Or remove a project anytime with

    $ <your path>/app/console project:remove -n <project name> -b <branch name>
