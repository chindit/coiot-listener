# coiot-listener

This small script allows you to easily listen for incoming CoIoT pushes from your Shelly devices

### How to use ?

1. As any PHP Project, `clone` and then `composer install` 
2. Launch `php bin/console coiot:listen`
3. Make sure your Shelly devices are set to unicast your target IP.
For example, if you are running this script on 192.168.1.1, Go to http://ip.of.your.shelly, then _Internet and Security_ > 
_Advanced - Developer Settings_ and change _CoIoT peer_ to your IP (so, 192.168.1.1)
4. See pushes flowing through your CLI ^^ 
5. Mapping of info can be done automatically by running `php bin/console generate:description`.
It's recommended to run this command in «append» mode for every device type you have. By default, some codes are already present but **I cannot guarantee they are the same for you**.
If you are having any king of issues, run the commande in «override» mode.

### Run as a daemon

In case you want to run this script as a daemon, use `coiot-listener.service` present at the base of this project.
Simply change path to your PHP binary and to this script, enable the service and you're good.

### Do something with the data

Of course… the goal is to do something with your data.

At the moment, you can simply go to `src/Command/CoIoTListener.php` and add whatever code you want after `$jsonString` 
