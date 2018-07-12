# crontablib
## Crontablib for cron will add remove and rewrite cron jobs on crontab!
## How use this lib?
### Add cronjob to crontab
```php
require_once 'crontab.php';
$Crontab = new Crontab();
$time = $Crontab->translate_timestamp(time(), 's i G * *');
$Crontab->add_job($time, 'SCRIPT_PATH.php');
```
### Remove cronjob from crontab
```php
$Crontab->remove_job($time, 'cli_script2.php');
```
### Replace cronjob in crontab
```php
$from_time = $Crontab->translate_timestamp(time(), 's i G * *');
$to_time   = $Crontab->translate_timestamp(time(), 's i * * *');
$Crontab->replace_time($from_time,$to_time, 'cli_script2.php');
```
### Replace cronjob in crontab
```php
$from_time = $Crontab->translate_timestamp(time(), 's i G * *');
$to_time   = $Crontab->translate_timestamp(time(), 's i * * *');
$Crontab->replace_time($from_time,$to_time, 'cli_script2.php');
```
### Remove all jobs from crontab
```php
$Crontab->remove_all_jobs();
```
### How customize tmp file path ?
#### You can open crontablib.php and find there on bottom somethink like:

```php
define('LIB_PATH'          , dirname(__FILE__).'/');
define('CRONFILE_PATH'     , LIB_PATH.'crontab');
define('CRONTMPLFILE_PATH' , LIB_PATH.'crontab.tmp');
```