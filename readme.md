# Prerequisites

You must create a UTF-8 database and a user with read/write privileges beforehand

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

In case of a problem, remove the folder created by composer and re-run the command.

# How it works

The deployment manager builds all projects the same way:

    1. Back up the current project content
    2. Create a temp directory
    3. Fetch the last version of the project in the temp directory
    4. Execute the specific steps of the build handler associated with the project
    5. Move the temp dir to the real project dir
    
Therefore, pretty much everything lies in the build handler you choose since it will determine the specific steps taken 
once the last version of the project has been fetched.

Only one out-of-the-box build handler is delivered with the project: the PHP Handler. Its specific steps are:

    1. Copy the dist config files
    2. Replace the dist parameters
    
# Add a custom build handler

But what if I want to execute different steps you ask ?
 
Well nothing is easier! All you have to do is create your own build handler implementing the HandlerInterface in the 
/src/Service/Build/Handler/Custom folder and more specifically implement a *getSpecificSteps* method that will
return the steps *you* want to execute during the deployment.

Once it's done, simply associate it with your project by giving `Custom\\MyAwesomeHandler` to the attribute `handler` 
of your project.

# Create a new project
    
Add a new project with

    $ <your path>/app/console project:add

# Remove a project
    
Remove a project with

    $ <your path>/app/console project:remove -n <project name> -b <branch name>
