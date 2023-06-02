<?php

include_once 'settings.php';

// JSON stuff
const JSON_URL = "https://api.v2.sondehub.org/amateur/telemetry";

// Software parameters (please leave this unchanged)
const SOFTWARE_NAME = "wspr2sondehub";
const SOFTWARE_VERSION = "v0.0.1";

/***********************************************************************
 * Init some important paramters
 * ********************************************************************/
function init_setup()
{ 
   echo "Setting UTC timezone\n\n";
   // We need the program to run in UTC time
   date_default_timezone_set('UTC');
   echo "Current date/time: ";
   // Just for checking
   echo date('Y-m-d H:i:s T', time()) . "\n\n\n";
}

/***********************************************************************
 * Convert query results to 
 * $source is an array indexed with numbers
 * ********************************************************************/
function convert_qry_result($source,$dest)
{
	// Convert the query results to a associative array
	// SELECT time,band,tx_sign,tx_loc,tx_lat,tx_lon,power,time
	$dest['time'] = $source[0];
	$dest['band'] = $source[1];
	$dest['tx_sign'] = $source[2];
	$dest['tx_loc'] = $source[3];
	$dest['tx_lat'] = $source[4];
	$dest['tx_lon'] = $source[5];
	$dest['power'] = $source[6];	
	$dest['frequency'] = $source[7];
	$dest['time_long'] = $source[8];
	return $dest;			
}

/***********************************************************************
 * Perform a query at the wspr database
 * ********************************************************************/
function perform_query($aQuery)
{
  $baseurl = 'http://db1.wspr.live/?';
  $query=http_build_query(array(
        'query' => $aQuery
         ));	
  $url = $baseurl . $query;       
  
  // Init curl session and add parameters
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_URL, $url );  
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  $content = curl_exec($ch);
  curl_close($ch);
  
  return $content;     
}


/************************************************************************************
* JSON structure setup for sondehub. 
*
*  Example:
* {
*  "software_name": "ttnhabbridge", # Receiving software name
*  "software_version": "0.0.1", # Receiving software version
*  "uploader_callsign": "foobar",                 # Mandatory - TTN station name?
*  "uploader_position": [ -34.0, 138.0, 0 ],  # Optional - TTN station location, if available
*  "uploader_radio": "???",  # Optional - Any other details 
*  "uploader_antenna": "???", # Optional - other rx details
*  "snr": 11.79, # Optional - Receiver metadata - SNR
*  "frequency": 434.201003, # Optional - Receiver Metadata - RX Frequency
*  "modulation": "LoRaWAN - TTNv3", # Optional, but recommended - Modulation type
*  "time_received": "2022-04-18T04:36:59.899304Z", # Time the packet was received on the TTN network
*  "datetime": "2022-04-18T04:36:58.000000Z", # Date/time reported by the payload itself. Use todays UTC date if no date available.
*  "payload_callsign": "CALLSIGN_HERE", # Callsign of the payload
*  "frame": 6, # Optional - Frame number 
*  "lat": -34.1, # Mandatory - Position
*  "lon": 138.1,
*  "alt": 100.0,
*  "temp": 30, # Some examples of optional fields 
*  "sats": 0,
*  "batt": 3.15,
* }
*
*
* Station info JSON
*{
  "software_name": "string",
  "software_version": "string",
  "uploader_callsign": "string",
  "uploader_position": [
    0,
    0,
    0
  ],
  "uploader_antenna": "string",
  "uploader_contact_email": "string",
  "mobile": true
}
*****************************************************************************************/
function decodeTelemetry($res1, $res2)
{
	// I reused parts of the python code created by sm3ulc
	// see: https://github.com/sm3ulc/hab-wspr 
	
	// These are the fields we need to decode
	$json = array(
	   //  uncomment 'dev' for sondehub test uploading
	  // 'dev'                 => 'true', 
	  'software_name'       => SOFTWARE_NAME,
	  'software_version'    => SOFTWARE_VERSION,
	  'uploader_callsign'   => UPLOADERCALL,
	  'frequency'           => 0.0,
	  'modulation'          => 'WSPR',
	  'time_received'       => '2023-01-01',
	  'datetime'            => '2023-01-01',
	  'payload_callsign'    => PAYLOAD,
	  'lat'                 => '0.0',
	  'lon'                 => '0.0',
	  'alt'                 => '0',
	  'temp'                => '0',
	  //'sats'                => '0',
	  'gps'                 => '0',
	  'batt'                => '0.0'
	);

	$json['frequency'] = floatval($res1['frequency']) / 1000000;
    $json['time_received'] = substr($res2['time'],0,10) . "T" . substr($res2['time'],11,8) . ".000000Z";
	$json['datetime'] = $json['time_received'];
		
	// Display all the known values on the console
	echo "\n";
    echo "\nSoftware name:      " . $json['software_name'];
	echo "\nSoftware version:   " . $json['software_version'];
	echo "\nUploader callsign:  " . $json['uploader_callsign'];
	echo "\nFrequency:          " . $json['frequency'];
	echo "\nModulation:         " . $json['modulation'];
	echo "\nTime received:      " . $json['time_received'];
	echo "\nDate/Time reported: " . $json['datetime'];
	echo "\nPayload callsign:   " . $json['payload_callsign'];

    // Calculate the other values
    // Power to decimal convertor, needed for decoding the power field
    $pow2dec = array("0"=>0,"3"=>1,"7"=>2,"10"=>3,"13"=>4,"17"=>5,"20"=>6,
                     "23"=>7,"27"=>8,"30"=>9,"33"=>10,"37"=>11,"40"=>12,
                     "43"=>13,"47"=>14,"50"=>15,"53"=>16,"57"=>17,"60"=>18);
    
   // First four chars of the maidenhead Grid (taken from the first wspr call)            
   $maidenHead = substr($res1['tx_loc'],0,4);	
   
   // Get the last two letters of the maidenhead
   // Convert call and locator to numbers
   // Is first character of second wspr call a letter?
   $c1 = $res2['tx_sign'][1];
   if (preg_match("/^[a-zA-Z]$/", $c1)) 
   {
      $c1 = ord($c1)-55;
   }
   else
   {
	  $c1 = ord($c1)-48;   
   }
   // Get the rest of the characters
   $c2 = ord($res2['tx_sign'][3])-65;
   $c3 = ord($res2['tx_sign'][4])-65;
   $c4 = ord($res2['tx_sign'][5])-65;
   
   // Convert locator to numbers
   $l1 = ord($res2['tx_loc'][0])-65;
   $l2 = ord($res2['tx_loc'][1])-65;
   $l3 = ord($res2['tx_loc'][2])-48;
   $l4 = ord($res2['tx_loc'][3])-48;
   
   // Convert power to number
   $p=$pow2dec[$res2["power"] ];
   
   // Do some calculations
   $sum1=$c1*26*26*26;
   $sum2=$c2*26*26;
   $sum3=$c3*26;
   $sum4=$c4;
   $sum1_tot=$sum1+$sum2+$sum3+$sum4;

   $sum1=$l1*18*10*10*19;
   $sum2=$l2*10*10*19;
   $sum3=$l3*10*19;
   $sum4=$l4*19;
   $sum2_tot=$sum1+$sum2+$sum3+$sum4+$p;
   
   $lsub1=intval($sum1_tot/25632);
   $lsub2_tmp=$sum1_tot-$lsub1*25632;
   $lsub2=intval($lsub2_tmp/1068);

   // Calculate the altitude
   $alt=($lsub2_tmp-$lsub2*1068)*20;
   
   // Decode the last two positions of the maidenhead
   $lsub1=$lsub1+65;
   $lsub2=$lsub2+65;
   $subloc=strtolower(chr($lsub1) . chr($lsub2));
   $maidenHead = $maidenHead . $subloc;
    
   echo "\nMaidenhead:         " . $maidenHead;
   
   // comment or uncomment this part. But if you want to use it, do not forget to 
   // add it to the json
 
   // Decode the temperature
   $temp_1=intval($sum2_tot/6720);
   $temp_2=$temp_1*2+457;
   $temp_3=$temp_2*5/1024;
   $temp=($temp_2*500/1024)-273;
   
   $json['temp'] = round($temp);
   echo "\nTemperature:        " . $json['temp']; 
   
   
   // Decode battery / solar
   $batt_1=intval($sum2_tot-$temp_1*6720);
   $batt_2=intval($batt_1/168);
   $batt_3=$batt_2*10+614;
   $batt=$batt_3*5/1024;
   
   $json['batt'] = $batt;
   echo "\nVoltage:            " . $json['batt'];
   
   // Decode Speed / GPS / Sats
   $t1=$sum2_tot-$temp_1*6720;
   $t2=intval($t1/168);
   $t3=$t1-$t2*168;
   $t4=intval($t3/4);
   $speed=$t4*2;
   $r7=$t3-$t4*4;
   $gps=intval($r7/2);
   $sats=$r7%2;
    
   echo "\nSpeed:              " . $speed; 
   
   // $json['sats'] = $sats;
   $json['gps'] = $gps;
   // echo "\nSatellites:         " . $json['sats'];  
   echo "\nGPS:                " . $json['gps'];   
   
   
   // Convert the maidenhead to coordinates
   $maidenHead = strtoupper($maidenHead);
   $N = strlen($maidenHead);
   $O = ord('A');
   $lon = -180;
   $lat = -90;
   $lon += (ord($maidenHead[0])-$O)*20;
   $lat += (ord($maidenHead[1])-$O)*10;
   // Second pair
   $lon += intval($maidenHead[2])*2;
   $lat += intval($maidenHead[3])*1;
   //third pair
   $lon += (ord($maidenHead[4])-$O) * 5./60;
   $lat += (ord($maidenHead[5])-$O) * 2.5/60;
   $lon += 2.5/60;
   $lat += 1.25/60;
   
   // Add longitude, latitude and altitude to the json
   $json['lon'] = $lon;
   $json['lat'] = $lat;
   $json['alt'] = $alt;
   
   echo "\nLatitude:           " . $json['lat'];
   echo "\nLongitude:          " . $json['lon'];
   echo "\nAltitude:           " . $json['alt'];

   // Upload spots to Sondehub   
   if (UPLOADSPOTS)
   {
     // Send the JSON the server
     $json_headers = array( "Content-Type: application/json",
                            "accept: text/plain");
                          
     $json_encoded = json_encode($json);
     $json_encoded = '[' . $json_encoded . ']';
      
     $channel = curl_init(JSON_URL);   
     curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
     curl_setopt($channel, CURLOPT_CUSTOMREQUEST, "PUT");
     curl_setopt($channel, CURLOPT_HTTPHEADER, $json_headers);
     curl_setopt($channel, CURLOPT_POSTFIELDS, $json_encoded);
     curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 10);
     $response = curl_exec($channel);
     $statusCode = curl_getInfo($channel, CURLINFO_HTTP_CODE);
     echo "\nUpload to Sondehub: " . $statusCode;
     echo "\nUpload to Sondehub: " . $response;
     curl_close($channel);  
   }
   
   echo "\n====================================================\n\n";
   
   // Write spots to a logfile
   if (LOGSPOTS)
   {
      $logfile = fopen(LOGFILE, "a");
      fwrite($logfile,date('Y-m-d H:i:s T', time()));fwrite($logfile,",");
      fwrite($logfile,PAYLOAD);fwrite($logfile,",");
      fwrite($logfile,$lat);fwrite($logfile,",");
      fwrite($logfile,$lon);fwrite($logfile,",");
      fwrite($logfile,$alt);
      fwrite($logfile,"\n");
      fclose($logfile);
   }
   
}

// Do setup
init_setup();

// Repeat until forever
while (true)
{
   // Create two empty arrays
   $msg1_results = array();
   $msg2_results = array();

   // Ceate query parameters

   // Query only records from the last 20 minutes
   $queryTime = strtotime('now -20 minutes');
   // Query only records in the correct frequency band
   $band = FREQBAND;
   // Query only records which are in the correct timeslots
   $callsign_timeslot = "____-__-__ __:_" . CALLSIGN_SLOT . "%";   
   $telemetry_timeslot = "____-__-__ __:_" . TELEMETRY_SLOT . "%";
   // Query only records with the correct Flight ID (example: '0_9%')
   $flightID = FLIGHT_ID_1 . "_" . FLIGHT_ID_3 . "%";
   // Query only records with the correct HAM call
   $myCall = HAMCALL;
   // Create the query for the first message
   $msg1 = perform_query("SELECT toString(time) as stime, band,tx_sign,tx_loc,tx_lat,tx_lon,power,frequency,time FROM wspr.rx WHERE (band='$band') AND (stime LIKE '$callsign_timeslot')  AND (time > $queryTime) AND (tx_sign='$myCall') ORDER BY time DESC LIMIT 1");
   // Create the query for the second message
   $msg2 = perform_query("SELECT toString(time) as stime, band,tx_sign,tx_loc,tx_lat,tx_lon,power,frequency,time FROM wspr.rx WHERE (band='$band') AND (stime LIKE '$telemetry_timeslot') AND (time > $queryTime) AND (tx_sign LIKE '$flightID') ORDER BY time DESC LIMIT 1");

   // Display query results on screen
   echo $msg1;
   echo $msg2;
   
   // Log the database query results to a file
   if (LOGRAWSPOTS)
   {
      $rawfile = fopen(RAWLOGFILE, "a");
      fwrite($rawfile,$msg1);
      fwrite($rawfile,$msg2);
      fclose($rawfile);
   }

   // Put the query results in arrays for easy reference
   // First message 1
   $array1 = explode("\t",$msg1);
   if (count($array1) == 9)
   {
      $msg1_results = convert_qry_result($array1, $msg1_results);
   }
   // Second message 2
   $array2 = explode("\t",$msg2);
   if (count($array2) == 9)
   {
     $msg2_results = convert_qry_result($array2, $msg2_results);
   }

   // Only bother decoding if bothqueries came back with valid results
   if ( (count($msg1_results) == 9) && 
        (count($msg2_results) == 9) 
      )
   {
     // Now we need to convert the query results to GPS coordinates.
     // But do not proceed if the time from the second query is smaller than the
     // time from the first query
     if ($msg2_results['time_long'] > $msg1_results['time_long'])
     {
        decodeTelemetry($msg1_results, $msg2_results);	
     }
     else
     {
		 echo "No upload: Time Received in message2 is older than Time Received in message1\n\n\n";
     }
   }
   else
   {
	   echo "\n\nFound no new spots...\n";
   }
   // Scrape the wspr database every 300 seconds
   sleep(300);
}


?>
