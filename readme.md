# Prerequisites

You must have PHP, GIT and Composer installed on your system

You must create a UTF-8 database and a user with read/write privileges on it beforehand

# Installation

Install the project with

    $ composer create-project asticode/php-deployment-manager .
    
And follow the instructions on the screen.

In case of a problem, remove the folder created and re-run the previous command.
    
You can add a new project anytime with

    $ ./app/console project:add
    
Or remove a project anytime with

    $ ./app/console project:remove -n <project name> -b <branch name>
