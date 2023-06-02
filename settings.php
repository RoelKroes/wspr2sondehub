<?php

// Log decoded spots ready for upload to local file on disk
const LOGSPOTS = true;
const LOGFILE = "spotslog_pe2bz-10m.csv";

// Log raw wspr database queries to file on disk
const LOGRAWSPOTS = true;    
const RAWLOGFILE = "rawlog_pe2bz-10m.csv";

// Upload spots to Sondehub
const UPLOADSPOTS = true;

// Balloon parameters
const PAYLOAD = "BZ-HAB-13";

// use the correct number to search in a specific frequency band
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

// Uploader parameters (visible on sondehub)
const UPLOADERCALL = "wspr2sondehub";

// WSPR HAM CALL (your real HAM call, used by the WSPR transmissions from your HAB)
const HAMCALL = "PE2BZ";

// Telemetry parameters
// See: http://hojoham.blogspot.com/2016/10/known-flight-ids.html
const CALLSIGN_SLOT = "2";
const TELEMETRY_SLOT = "4";
const FLIGHT_ID_1 = "1";
const FLIGHT_ID_3 = "8";

?>

