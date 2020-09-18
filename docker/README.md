# Filebin Docker Container

## Filebin
Filebin is a paste service developed by Florian Pritz [https://paste.xinu.at/](https://paste.xinu.at/)

## Dockerfile
[Dockerfile](https://github.com/Bluewind/filebin/blob/master/Dockerfile)

## Ports
The PHP webserver is listening on ```8080```

## Volumes

- **Uploaded Data:** uploaded files are saved to ```/var/lib/filebin/filebin/data/uploads```
- **Advanced Configuration:** the configuration is located at ```/var/lib/filebin/filebin/application/config```

## Environment Variables
- **FB_DB_HOSTNAME:** the hostname of the mysql/mariadb server
- **FB_DB_USERNAME:** the username for the mysql/mariadb server
- **FB_DB_PASSWORD:** the password for the mysql/mariadb server
- **FB_DB_DATABASE:** the database on the mysql/mariadb for Filebin

- **FB_CONTACT_NAME:** Contact Name
- **FB_CONTACT_MAIL:** Contact E-Mail (will be used as email for the first user)

- **FB_SMTP_HOST:** Address of the SMTP Server
- **FB_SMTP_PORT:** Port for SMTP Server (default 587)
- **FB_SMTP_USER:** Username for SMTP Server (will also be used as mail from)
- **FB_SMTP_PASSWORD:** Password for the SMTP Server Useraccount

## First User
The first user is **admin** with the password **admin**

## Run

### with docker-compose
```bash
docker-compose build
docker-compose up
```

### with linked mysql/mariadb database server
```docker run -ti --rm -p <port>:8080 --link mdb:mysql  -e FB_DB_HOSTNAME=mysql -e FB_DB_USERNAME=filebin_usr -e FB_DB_PASSWORD=test -e FB_DB_DATABASE=filebin -e FB_CONTACT_NAME="John Doe" -e FB_CONTACT_MAIL="john.doe@localmail.local"  sebastianrakel/filebin```
