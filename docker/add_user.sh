#!/bin/bash

cd ${FILEBIN_DIR}
printf "%s\n%s\n%s\n" admin ${FB_CONTACT_MAIL} admin | php index.php user add_user
