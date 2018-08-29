#!/bin/bash

#set -euo pipefail

function set_mail_config() {
cat <<EOF > ${FILEBIN_HOME_DIR}/msmtprc	
account filebinmail
tls on
tls_certcheck off
auth on
host ${FB_SMTP_HOST}
port ${FB_SMTP_PORT}
user ${FB_SMTP_USER}
from ${FB_SMTP_USER}
password ${FB_SMTP_PASSWORD}
EOF

chmod 600 ${FILEBIN_HOME_DIR}/msmtprc	
}

function set_config() {
    FB_ENCRYPTION_KEY=`< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c32`
cat <<EOF >${FILEBIN_DIR}/application/config/config-local.php
<?php
\$config['base_url'] = 'http://127.0.0.1:8080/';
\$config['encryption_key'] = '${FB_ENCRYPTION_KEY}';
\$config['email_from'] = '${FB_SMTP_USER}';
EOF
}

function set_database_config() {
cat <<EOF >${FILEBIN_DIR}/application/config/database.php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
\$active_group = 'default';
\$query_builder = TRUE;

\$db['default'] = array(
	'dsn'	=> 'mysql:host=${FB_DB_HOSTNAME};dbname=${FB_DB_DATABASE}',
	'hostname' => '',
	'port'	=> 3306,
	'username' => '${FB_DB_USERNAME}',
	'password' => '${FB_DB_PASSWORD}',
	'database' => '',
	'dbdriver' => 'pdo',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'char_set' => 'utf8mb4', // if you use postgres, set this to utf8
	'dbcollat' => 'utf8mb4_bin', // if you use postgres, set this to utf8_bin
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => TRUE,
	'failover' => array(),
	'save_queries' => TRUE
);
EOF
}

# wait for DB to be ready
while ! nc "$FB_DB_HOSTNAME" 3306 </dev/null >/dev/null; do
	echo "Waiting for database"
	sleep 0.5
done


if [[ ! -e $FILEBIN_DIR/application/config/config-local.php ]]; then
    echo "no config found, new config will be generated"

    set_config
    set_database_config
    set_mail_config

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
