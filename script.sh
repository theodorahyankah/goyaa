#!/bin/bash
(crontab -l | grep -v "ree ./artisan email:free-trial-end-mail") | crontab -
(crontab -l; echo "0 0 * * * ree ./artisan email:free-trial-end-mail") | crontab -
