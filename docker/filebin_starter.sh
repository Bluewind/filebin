#!/bin/bash

function set_config() {
    FB_ENCRYPTION_KEY=`< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c32`
     sed -i "s/\$config\['encryption_key'\] = ''/\$config['encryption_key'] = '${FB_ENCRYPTION_KEY}'/" ${FILEBIN_DIR}/application/config/config-local.php
}

function set_database_config() {
    sed -i "s/\$db\['default'\]\['hostname'\] = .*/\$db['default']['hostname'] = \"${FB_DB_HOSTNAME}\";/" ${FILEBIN_DIR}/application/config/database.php
    sed -i "s/\$db\['default'\]\['username'\] = .*/\$db['default']['username'] = \"${FB_DB_USERNAME}\";/" ${FILEBIN_DIR}/application/config/database.php
    sed -i "s/\$db\['default'\]\['password'\] = .*/\$db['default']['password'] = \"${FB_DB_PASSWORD}\";/" ${FILEBIN_DIR}/application/config/database.php
    sed -i "s/\$db\['default'\]\['database'\] = .*/\$db['default']['database'] = \"${FB_DB_DATABASE}\";/" ${FILEBIN_DIR}/application/config/database.php
}

if [[ ! -e $FILEBIN_DIR/application/config/config-local.php ]]; then
    echo "no config found, new config will be generated"
    cp $FILEBIN_DIR/application/config/example/config-local.php ${FILEBIN_DIR}/application/config/config-local.php

    set_config
    set_database_config

    CONTACT_INFO_FILE=${FILEBIN_DIR}/data/local/contact-info.php
    cp $FILEBIN_DIR/data/local/examples/contact-info.php ${CONTACT_INFO_FILE}

    sed -i "s/John Doe/${FB_CONTACT_NAME}/" ${CONTACT_INFO_FILE}
    sed -i "s/john.doe@example.com/${FB_CONTACT_MAIL}/" ${CONTACT_INFO_FILE}

    ${FILEBIN_DIR}/scripts/install-git-hooks.sh
    ${FILEBIN_DIR}/git-hooks/post-merge

    ${FILEBIN_HOME_DIR}/add_user.sh
fi

cd $FILEBIN_DIR/public_html
php -S 0.0.0.0:8080
