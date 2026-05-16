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

# Serve public non-PHP files directly
RewriteCond %{REQUEST_URI} !\.php$ [NC]
RewriteCond %{DOCUMENT_ROOT}/public%{REQUEST_URI} -f
RewriteRule ^(.+)$ public/$1 [L]

RewriteCond %{REQUEST_URI} !^/index\.php$
RewriteRule ^ index.php [L,QSA]
```

With this setup, `public/home.png` is available as `/home.png`, but `public/index.php` is never served as `/index.php`.

If you use Nginx, add this inside your `server` block.

``` nginx
location = /index.php {
    # Your PHP/FPM config here
}

location ~ \.php$ {
    rewrite ^ /index.php last;
}

location / {
    try_files /public$uri /index.php;
}
```

With this setup, `public/home.png` is available as `/home.png`, but `public/index.php` is never served as `/index.php`.

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
