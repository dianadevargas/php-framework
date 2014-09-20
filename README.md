PHP tiny framework
=========

##Philosophy
This PHP framework is light weight (less than 700Kb), with Front Controller, MVC and OO structure. It is perfect for small tools and apps that required a structured approach without the complications of a big framework.

##Directory Structure
The directory structure has been organized as follow:
* Application :  contains folders such as *config*, *views*, *commands*, and *models*. Most of your application's code will reside in this directory.
* Cron : Contain examples of files to be call from command line or cron jobs
* Library : Contains the core and third party classes. 
* public_html: Contain the index.php file, images, js, and css files (this is the public directory)

##The framework has a few system requirements:
*	PHP >= 5.5.12
*	MySQL

##Configuration
The framework needs almost no configuration. You are free to get started developing However, you may wish to review the *application/default/config/config.ini* file. This file contains several options such as database logins that you may wish to change according to your application.

##Permissions
The framework requires one set of permissions to be configured - folders within *public_html* require write access by the web server (like apache).

##Pretty URLs
The framework has a *.htaccess* file that is used to allow URLs sending all call to index.php. If you use Apache to serve your application, be sure to enable the mod_rewrite module.
If the .htaccess file does not work with your Apache installation, try this one:
```
Options +FollowSymLinks
RewriteEngine On
RewriteRule !\.(js|gif|png|jpg|css|swf|xml|pdf|exe|cab)$ index.php [L]
```

##Request Lifecycle

###Overview
The framework request lifecycle is fairly simple. A request enters your application via the url (http://your.domain/) the file *public_html/index.php* is called. This file includes the bootstrap.php file which calls the front controller. The front controller calls the appropriated  command. The response from that command is then sent back to the browser and displayed on the screen. 
The selection of the command is based in the url eg. *http://your.domain/modulename/tools* calls a command in *application/modulename/commands/tools.php* if this does not exist try to call the method *"tools"* in *"application/modulename/commands/index.php"*

All folders in the application folder require having a *commands/index.php* file. The basic structure of this file is:
```
/* 
 * This is an example of a controller
 *  
 */   

class appname extends  library\Command
{
    public function index ()
    {
        $this->viewHTML('hello_world.html');
    }
}
/*
 *  Run if called with bootstrap
 */
$cmd = appname::getInstance();
```

###Views & Responses
####Basic Responses

They are three basic responses generated : json, file and html . the Command class has the methods *viewHTML, viewJSON, viewFILE* that send the appropriated header and display the file in the views folder.

####Views
Views typically contain the HTML of your application and provide a convenient way of separating your controller and domain logic from your presentation logic. Views are stored in the *appname/views* directory.
A simple view could look something like this:
```
<!-- View stored in modulename/views/greeting.php -->

<html>
    <body>
        <h1>Hello, <?php echo $vars->name; ?></h1>
    </body>
</html>
```

####Passing Data To Views
The view contains a reference to the singleton class *Registry* in the variable $vars, all the variables stored in the object can be seen on the view.
In the example above the variable *$vars->name* would be accessible from the view and can be set up in the *$modulename* object via its reference to the *registry* object.
####Special Responses

#####Creating A JSON Response
`echo json_encode($vars->response);`
#####Creating A File Download Response
`echo $vars->response;`

##Controllers
###Basic Controllers
Instead of defining all of your logic in a single *index.php* file, you may wish to organize this behaviour using Command classes. Command can group related route logic into a class, as well as take advantage of more advanced framework features such as automatic dependency.
Command are typically stored in the *modulename/commands* directory.

Here is an example of a basic command class:
```
class UserCommand extends Command{
    /**
     * Show the profile for the given user.
     */
    public function showProfile($id)
    {
        $user = User::find($id);

        $this->viewHTML ('user.html');
    }
}
```

All commands should extend the *Command* class. This class is stored in the library directory, and may be used as a place to put shared command logic.
You can use classes specifically for the module these classes should be stored in   *modulename/models* path.

###Handling Missing Methods
An *index* method may be defined in all commands; this method is called when the received command does not match any public  method in the command object on a given application.

###Examples
The framework includes one example method.
* Example: quick app for testing code and display database data.



