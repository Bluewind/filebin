#!/bin/bash

cd ${FILEBIN_DIR}
echo "Creating initial user. If it exists, this will show an error message instead"
printf "%s\n%s\n%s\n" admin ${FB_CONTACT_MAIL} admin | php index.php user add_user
