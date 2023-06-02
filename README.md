# wspr2sondehub

wspr2sondehub is a simple program written in PHP to scrape the wspr database for High Altitude Balloons, decode the telemetry, log the telemetry in a local file and post the telemetry on amateur.sondehub.org
It is very, very basic but it runs.

The program follows the protocol as described at: https://www.qrp-labs.com/flights/s4#protocol
Currently I use it for my own balloons. Feel free to use or modify it.

The program is written in PHP and can be installed on almost any computer.

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
