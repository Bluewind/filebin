FROM alpine:edge
MAINTAINER Sebastian Rakel <sebastian@devunit.eu>

RUN apk add --no-cache -X http://dl-cdn.alpinelinux.org/alpine/edge/testing bash php5 py-pygments py2-pip imagemagick php5-gd nodejs composer php5-pdo_mysql php5-exif

ENV FILEBIN_HOME_DIR /var/lib/filebin
ENV FILEBIN_DIR $FILEBIN_HOME_DIR/filebin

ADD . $FILEBIN_DIR

RUN adduser -S -h $FILEBIN_HOME_DIR filebin
RUN chown filebin: -R $FILEBIN_HOME_DIR 

RUN pip install ansi2html

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
