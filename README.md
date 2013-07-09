Web Replay
==========



PHP configuration
-----------------

I use Nginx as a webserver and php-fpm as the PHP application server.

My fpm-server listens to the port number 8999.

```
always_populate_raw_post_data = On
```

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
                fastcgi_param   SCRIPT_FILENAME $document_root/index.php;

                add_header Access-Control-Allow-Origin *;
        }
}
```


