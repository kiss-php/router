# HTTP ROUTER

Requirements:
- PHP >7.4
- Composer (vendor autoload)

To install you execute:
``` bash
composer install ...
```

If you use Apache, create a `.htaccess` file next to your `index.php` file.

``` apache
RewriteEngine On

RewriteCond %{REQUEST_URI} !^/index\.php$
RewriteRule ^ index.php [L,QSA]
```

To start to use add the routes in your index.php file.
``` php
require 'vendor/autoload.php';

use Kiss\Http\Router;

Router::use('', 'Agrandesr\EasyRouter\Controllers\User'); //Function default main
Router::use('get','Agrandesr\EasyRouter\Controllers\User::get');
Router::use('update','Agrandesr\EasyRouter\Controllers\User::update');
```

If you want to serve files from a folder, use `useFolder`.

``` php
Router::useFolder('img', 'public/images/');
```

This serves `img/logo.png` from `public/images/logo.png`, and `img/128x128/logo.png` from `public/images/128x128/logo.png`.

If you prefer to execute a PHP file directly, use `execute`.

``` php
Router::execute('about', '/pages/about.php');
```

If you want to create relative path you can use brackets.

``` php
Router::use('update/{id}','Agrandesr\EasyRouter\Controllers\User::update');
```

Next, you can use this relative path with the static function *getOption*.

``` php
$id = Router::getOption('id');
```
