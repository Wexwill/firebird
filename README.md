## General information
Drupal site version 10.2.4.

Admin user.
login:firebird/pw:firebird.

Deployed on Docker-based Drupal stack wodby/docker4drupal.
env file for deployment is in the archive.The archive also contain files folder, database dump and settings.php file in which the api key is stored.


## Converter service
To access the form and service, you need to install the module Firebird.
Before using the form, you need to run cron, which will receive data from the freecurency API and add it to the currency_exchange table.
A simple form for exchanging currencies was created and located at '/admin/currency-exchange', it uses a service with method сonvert() to calculate the amount of currency.
Exchange rate data is stored in the database.





