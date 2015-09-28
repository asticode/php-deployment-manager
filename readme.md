# Prerequisites

You must have PHP, GIT and Composer installed on your system

You must create a UTF-8 database and a user with read/write rights on it beforehand

# Installation

Import the project with

    $ composer create-project asticode/php-deployment-manager .
    
Make sure the console is executable with

    $ sudo chmod +x ./app/console
    
Install the manager with

    $ ./app/console manager:install
    
You can add a new project anytime with

    $ ./app/console project:add
    
Or remove a project anytime with

    $ ./app/console project:remove -n <project name> -b <branch name>
