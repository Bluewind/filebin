# Setup

For installation instructions follow `./INSTALL`.

## Fancy URLs

Set `$config['index_page'] = '';` in `application/config/config-local.php` and adjust your webserver's rewrite config.

### Apache

See the shipped htaccess.txt

### Nginx

```
location / {
    try_files $uri $uri/ @ee;
}
location @ee {
    rewrite ^(.*) /index.php?$1 last;
}
```

### Lighttpd

```
url.rewrite-if-not-file = ( "^(.*)$" => "/index.php/?$1" )
```
