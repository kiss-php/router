# HTTP ROUTER

Requirements:
- PHP >7.4
- Composer (vendor autoload)

To install you execute:
``` bash
composer install ...
```

To start to use add the routes in your index.php file.
``` php
require 'vendor/autoload.php';

use Kiss\Http\Router;

Router::use('', 'Agrandesr\EasyRouter\Controllers\User'); //Function default main
Router::use('get','Agrandesr\EasyRouter\Controllers\User::get');
Router::use('update','Agrandesr\EasyRouter\Controllers\User::update');
```

If you want to create relative path you can use brackets.

``` php
Router::use('update/{id}','Agrandesr\EasyRouter\Controllers\User::update');
```

Next, you can use this relative path with the static function *getOption*.

``` php
$id = Router::getOption('id');
```
