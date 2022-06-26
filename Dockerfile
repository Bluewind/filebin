FROM alpine:3
LABEL maintainer="sebastian@devunit.eu"

RUN apk add --no-cache bash php8 py-pygments py-pip imagemagick php8-gd nodejs composer php8-pdo_mysql php8-exif php8-ctype php8-session git php8-fileinfo msmtp php8-pgsql php8-phar php8-json
RUN ln -sf /usr/bin/python3 /usr/bin/python

ENV FILEBIN_HOME_DIR /var/lib/filebin
ENV FILEBIN_DIR $FILEBIN_HOME_DIR/filebin

ADD . $FILEBIN_DIR

RUN adduser -S -h $FILEBIN_HOME_DIR filebin
RUN chown filebin: -R $FILEBIN_HOME_DIR

RUN pip install ansi2html

RUN sed -i 's+.*sendmail_path =.*+sendmail_path = "/usr/bin/msmtp -C ${FILEBIN_HOME_DIR}/msmtprc --logfile ${FILEBIN_HOME_DIR}/msmtp.log -a filebinmail -t"+' /etc/php8/php.ini

USER filebin

ADD docker/filebin_starter.sh $FILEBIN_HOME_DIR
ADD docker/add_user.sh $FILEBIN_HOME_DIR

WORKDIR $FILEBIN_DIR

RUN cp ./application/config/example/* ./application/config/
RUN rm ./application/config/config-local.php

RUN php ./check_deps.php

WORKDIR $FILEBIN_HOME_DIR

EXPOSE 8080

VOLUME ["$FILEBIN_DIR/application/config", "$FILEBIN_DIR/data/uploads"]

ENTRYPOINT ["bash", "-c"]
CMD ["./filebin_starter.sh"]
