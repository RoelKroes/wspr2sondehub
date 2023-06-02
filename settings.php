<?php

// Log decoded spots ready for upload to local file on disk
const LOGSPOTS = true;
const LOGFILE = "spotslog.csv";

// Log raw wspr database queries to file on disk
const LOGRAWSPOTS = true;    
const RAWLOGFILE = "rawlog.csv";

// Upload spots to Sondehub
const UPLOADSPOTS = true;

// Balloon parameters
// Name of the payload as show on Sondehub
// CHANGE THIS
const PAYLOAD = "MY_HAB";

// use the correct number to search the wspr database for a specific frequency band
// 3  = 80m
// 7  = 40m
// 14 = 20m
// 28 = 10m
// 21 = 15m
// ...
// 144 = 2m
// 432 = 70cm
// 1296 = 23cm
const FREQBAND = "28"; // Only get 10m spots

// Uploader parameters (visible on sondehub as the uploader)
const UPLOADERCALL = "wspr2sondehub";

// WSPR HAM CALL (your real HAM call, used by the WSPR transmissions from your HAB)
// CHANGE THIS!
const HAMCALL = "MYCALL";

// Telemetry parameters
// See: http://hojoham.blogspot.com/2016/10/known-flight-ids.html
// CHANGE THIS
const CALLSIGN_SLOT = "4";
const TELEMETRY_SLOT = "6";
const FLIGHT_ID_1 = "1";
const FLIGHT_ID_3 = "6";

?>

