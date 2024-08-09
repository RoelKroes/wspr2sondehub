# wspr2sondehub

wspr2sondehub is a simple program written in PHP to scrape the wspr database every 5 minutes for telemetry from High Altitude Balloons, decode this telemetry, log the telemetry in a local file and post the telemetry on amateur.sondehub.org
It is still very, very basic but it runs.

The program follows the protocol as described at: https://www.qrp-labs.com/flights/s4#protocol
Currently I use it for my own balloons. 

The program is written in PHP and can be installed on almost any computer.

Feel free to improve and use this program.

# php
You can find many webpages on how to install PHP on Windows and Unix.

Be sure that in your phpi.ini file, in the [curl] section, curl.cainfo points to the cacert.pem file that is a part of this repository.
Windows example:
curl.cainfo ="C:\php\cacert.pem" 

And that the "curl" extension is enabled.
Windows Example:
extension=curl

# settings
First open the settings.php file and change the settings. This is very important.

# Run the program
Run the program from the command line:
php wspr2sondehub

# notes
Please, only run wspr2sondehub for balloons that you own yourself or that you have permission for from the owner. 
Otherwise you might not have the required details to post the correct data. Thanks!

