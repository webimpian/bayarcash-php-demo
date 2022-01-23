
# Demo implementation to Bayarcash payment gateway using vanilla PHP

* Edit the config.php according to your development environment.
* Configure cron.php to receive updated payment statuses.
* Activate the cron.php as a cron job using either of the choices:
    - Linux : https://stackoverflow.com/a/22358929/9427310
    - Windows : https://www.vivekmoyal.in/cron-job-in-php-on-localhost-in-windows-scheduler-in-php/
    - External service : https://cron-job.org/en/, or any external cron services
* Example Linux cron command to run cron.php for every 5 minutes: 
  ```shell
  */5 * * * * /usr/bin/php /var/www/ecommerce/cron.php >> /dev/null 2>&1
  ```
