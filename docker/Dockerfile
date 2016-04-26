FROM ubuntu:14.04
MAINTAINER Sebastian Rakel <sebastian@devunit.eu>

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update -y && \
    apt-get install -y php5 php5-mysql php5-gd php5-imagick libtext-markdown-perl expect \
                       python-pygments qrencode  imagemagick git python-pip realpath curl apt-transport-https

RUN echo 'deb https://deb.nodesource.com/node_4.x trusty main' > /etc/apt/sources.list.d/nodesource.list
RUN echo 'deb-src https://deb.nodesource.com/node_4.x trusty main' >> /etc/apt/sources.list.d/nodesource.list

RUN curl -s https://deb.nodesource.com/gpgkey/nodesource.gpg.key | apt-key add -

RUN apt-get update -y && \
    apt-get install -y nodejs

RUN sudo pip install ansi2html

ENV FILEBIN_HOME_DIR /var/lib/filebin
ENV FILEBIN_DIR $FILEBIN_HOME_DIR/filebin

RUN useradd -m -g users -d $FILEBIN_HOME_DIR filebin

USER filebin

ADD filebin_starter.sh $FILEBIN_HOME_DIR
ADD add_user.sh $FILEBIN_HOME_DIR

RUN git clone https://git.server-speed.net/users/flo/filebin/ $FILEBIN_DIR

WORKDIR $FILEBIN_DIR

RUN cp ./application/config/example/* ./application/config/
RUN rm ./application/config/config-local.php

RUN php ./install.php

WORKDIR $FILEBIN_HOME_DIR

EXPOSE 8080

VOLUME ["$FILEBIN_DIR/application/config", "$FILEBIN_DIR/data/uploads"]

ENTRYPOINT ["bash", "-c"]
CMD ["./filebin_starter.sh"]
