Web Replay
==========



PHP configuration
-----------------

I use Nginx as a webserver and php-fpm as the PHP application server.

My fpm-server listens to the port number 8999.

Requires mysqli.



Nginx configuration
-------------------

```
server {
        listen 80;
        server_name     webreplay.local;
        access_log      /var/log/nginx/webreplay.access.log;

        root /srv/http/webreplay;

        location /favicon.ico {
                return 404;
        }

        location / {
                include         fastcgi_params;

                fastcgi_pass    localhost:8999;
                fastcgi_param   SCRIPT_FILENAME $document_root/webreplay.php;

                add_header Access-Control-Allow-Origin *;
        }
}
```


Unittest setup
--------------

Requires PHPUnit
Requires Curl
Requires pecl_http

```
pear config-set auto_discover 1
pear install pear.phpunit.de/PHPUnit
apt-get install php5-curl
apt-get install libcurl3-dev
pecl install pecl_http
...accept defaults...
```

We need to enable the extension in PHP. Create the file **/etc/php/mods-available/http.ini** with the following content:

```
; configuration for php HTTP module
; priority=20
extension=http.so
```

Now enable the module by symlinking
```
cd /etc/php/conf.d
ln -s ../mods-available/http.ini 20-http.ini
service php5-fpm restart
```
