Candle
======

Simple PHP MVC framework with its own ORM

Installation
------------

1. Download [Candle](https://github.com/lyubo-slavilov/candle/archive/master.zip) as ZIP
2. Extract the ZIP archive to your project folder
3. On your machine create new local domain. For example [candle.local](http://candle.local)
4. Set your web server (Apache for example) document root to `<project-folder>/web`
5. Candle is now installed.

Creating an application
-----------------------

1. Once you've installed Candle, open [http://candle.local/rad.php](http://candle.local/rad.php)
2. You will see a list with two installed applications *Demo* and *Rad*. Bellow the list click on the link *Create New Application*
3. Fill the form with following information:
  1. *Application Name* - specifies the system application name. It is recomended to use CamelCased names. For example `MyApplication`
  2. *Front Controller* - specifies the application's front controller file name. For example `myapp.php`.
5. Click *Create*
4. Your application is now created. Test it by navigating to the specified front controller. For example [http://candle.local/myapp.php](http://candle.local/myapp.php)

Developing
----------
Once you have created an application by using Candle RAD, you will have:

1. *Main Controller* - Good starting point for your application. The controller will have some actions which will handle the home page and error pages.
2. A routing file `<project-dir>/app/<your-app>/routes.php` with two route rules available.
3. A bunch of templates in the `<your-app>/View/` directory:
  1. `layout.phtml` - representing the application layout.
  2. `main/homepage.phtml` - representing the home page content.
  3. `main/error404` - representing a a HTTP 404 error page.
  4. `main/error500` - representing a a HTTP 500 error page.
4. A `config.ini` file with basic application configuration.

Now everithing is in your hands. Start developing your application by changing your *Controllers* and *Views*!
