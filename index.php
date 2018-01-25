<?php

// Set $site_id to any value from this list
// 
// 10 - Angeles (MarQuee Mall,Angeles City)
// 11 - Bacolod
// 12 - Baguio
// 14 - Butuan
// 15 - Cagayan De Oro
// 16 - Calasiao
// 17 - Cebu
// 18 - Cotabato
// 4 - DFA Manila (Aseana)
// 6 - DFA NCR East (Megamall)
// 423 - DFA NCR North (Robinsons Novaliches)
// 7 - DFA NCR Northeast (Ali Mall)
// 8 - DFA NCR South (Alabang)
// 9 - DFA NCR West (SM Manila)
// 19 - Davao
// 20 - Dumaguete
// 21 - General Santos
// 22 - Iloilo
// 23 - La Union
// 24 - Legazpi
// 13 - Lipa
// 25 - Lucena
// 27 - Pampanga (Robinsons Starmills,San Fernando)
// 26 - Puerto Princesa
// 28 - Tacloban
// 29 - Tuguegarao
// 30 - Zamboanga
// 
// 
// Sample Data
// 
// Request:
// fromDate:2018-01-19
// toDate:2018-06-30
// siteId:17
// requestedSlots:1
//
// Response
// AppointmentDate:1516579200000
// IsAvailable:false

$dir = realpath(dirname(__FILE__));
$lockfile = $dir . "/script.lock";
$logfile = $dir . '/error.log';
$site_id = 17;

$mail = "EMAILHERE@MAIL.COM";

function _msg($message) {
	global $logfile;
	error_log($message . "\n", 3, $logfile);
}

// Check lock file
if (file_exists($lockfile)) {
	exit;
}

touch($lockfile);

$random = rand(0, 2);
sleep($random * 60);

$url = "https://www.passport.gov.ph/appointment/timeslot/available";

_msg("START: " . date("H:i:s Y/m/d"));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	"fromDate" => date("Y-m-d"),
	"toDate" => "2018-06-30",
	"siteId" => $site_id, // See siteId list
	"requestedSlots" => 1, // Max 5
));

$useragents = array(
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36",
	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36",
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0"
);

curl_setopt($ch, CURLOPT_USERAGENT, $useragents[rand(0, 2)]);
curl_setopt($ch, CURLOPT_REFERER, base64_decode('aHR0cHM6Ly93d3cucGFzc3BvcnQuZ292LnBoL2FwcG9pbnRtZW50L2luZGl2aWR1YWwvc2NoZWR1bGU='));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	base64_decode('SG9zdDogd3d3LnBhc3Nwb3J0Lmdvdi5waA=='),
	base64_decode('T3JpZ2luOiBodHRwczovL3d3dy5wYXNzcG9ydC5nb3YucGg='),
));

$response = curl_exec($ch);
curl_close($ch);

$values = json_decode($response);
if (!is_array($values)) {
	_msg("No values retrieved");
	_msg("END: " . date("H:i:s Y/m/d"));
	unlink($lockfile);
	exit;
}

$valid = array();
foreach ($values as $date) {
	if ($date->IsAvailable) {
		$valid[] = $date;
	}
}

if ($valid) {
	$message = "";
	foreach ($valid as $date) {
		$message .= "Date: " . date("F j", ($date->AppointmentDate / 1000)) . "\n" . PHP_EOL;
	}

	_msg("VALID:");
	_msg($message);

	if (file_exists($dir . "/tuturu_1.mp3")) {
		shell_exec("play " . $dir . "/tuturu_1.mp3 2>/dev/null");
	}

	if (mail($email, "[DFA] Valid Dates", $message)) {
		_msg("EMAIL SENT");
	}
}

_msg("END: " . date("H:i:s Y/m/d"));
unlink($lockfile);