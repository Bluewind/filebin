# Filebin Docker Container

## Filebin
Filebin is a paste service developed by Florian Pritz [https://paste.xinu.at/](https://paste.xinu.at/)

## Dockerfile
[Dockerfile](https://git.server-speed.net/users/flo/filebin/tree/docker/Dockerfile)

## Ports
The PHP webserver is listening on ```8080```

## Volumes

- **Uploaded Data:** uploaded files are saved to ```/var/lib/filebin/data/uploads```
- **Advanced Configuration:** the configuration is located at ```/var/lib/filebin/application/config```

## Environment Variables
- **FB_DB_HOSTNAME:** the hostname of the mysql/mariadb server
- **FB_DB_USERNAME:** the username for the mysql/mariadb server
- **FB_DB_PASSWORD:** the password for the mysql/mariadb server
- **FB_DB_DATABSE:** the database on the mysql/mariadb for Filebin

- **FB_CONTACT_NAME:** Contact Name
- **FB_CONTACT_MAIL:** Contact E-Mail (will be used as email for the first user)

## First User
The first user is **admin** with the password **admin**

## Run
### with linked mysql/mariadb database server
```docker run -ti --rm -p <port>:8080 --link mdb:mysql  -e FB_DB_HOSTNAME=mysql -e FB_DB_USERNAME=filebin_usr -e FB_DB_PASSWORD=test -e FB_DB_DATABASE=filebin -e FB_CONTACT_NAME="John Doe" -e FB_CONTACT_MAIL="john.doe@localmail.local"  sebastianrakel/filebin```
