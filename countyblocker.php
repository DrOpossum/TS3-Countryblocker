<?php

// Teamspeak-Config

$teamspeakInfo = array(
    'username' => '',
    'password' => '',
    'host' => '',
    'portQuery' => '10011',
    'portServer' => '9987',
    'displayname' => ''
);

// Array with 2 Letter Country Codes
$blacklist = array('HU'); // array('DE', 'HU');
$whitelist = array('DE', 'CH', 'AT', 'NL', 'FR', 'EN', 'US'); // array('DE', 'HU');
$clientType = 1; // 1 = Everyone, 2 = Ignore Query, 3 = Ignore Clients
$listMode = 1; // 1 = blacklist, 2 = whitelist
$punishMode = 1; // 1 = kick, 2 = ban
$punishMessage = 'Your Country is blocked'; // Message the user will see

/*
|--------------------------------------------------------------------------------
|   Do not modify anything below this area unless you know what you're doing.
|--------------------------------------------------------------------------------
*/

require_once("lib/ts3admin.class.php");
$tsAdmin = new ts3admin($teamspeakInfo['host'], $teamspeakInfo['portQuery']);

if ($tsAdmin->getElement('success', $tsAdmin->connect())) {

    $tsAdmin->login($teamspeakInfo['username'], $teamspeakInfo['password']);
	$tsAdmin->selectServer($teamspeakInfo['portServer']);
	$tsAdmin->setName($teamspeakInfo['displayname']);
	$connectionInfo = $tsAdmin->whoAmI() ['data'];
    
    for (;;) {
		$clients = $tsAdmin->clientList("-country -ip");
		foreach($clients['data'] as $client) {
			if ($listMode == 1) {
				$invalidCountry = false;
				foreach($blacklist as $blacklistCountry) {
					if ($client['client_country'] == $blacklistCountry || $client['client_country'] == "") {
						switch ($clientType) {
						case '1':
							$invalidCountry = true;
							break;

						case '2':
							if ($client['client_type'] == 0) {
								$invalidCountry = true;
							}

							break;

						case '3':
							if ($client['client_type'] == 1) {
								$invalidCountry = true;
							}

							break;
						}
					}
				}
			} elseif ($listMode == 2) {
				$invalidCountry = true;
				foreach($whitelist as $whitelistCountry) {
					if ($client['client_country'] == $whitelistCountry) {
						switch ($clientType) {
						case '1':
							$invalidCountry = false;
							break;

						case '2':
							if ($client['client_type'] == 0) {
								$invalidCountry = false;
							}

							break;

						case '3':
							if ($client['client_type'] == 1) {
								$invalidCountry = false;
							}

							break;
						}
					}
				}
			}

			if ($invalidCountry && $connectionInfo['client_id'] != $client['clid']) {
				if ($punishMode == 1) {
					$tsAdmin->clientKick($client['clid'], "server", $punishMessage);
				} elseif ($punishMode == 2) {
					$tsAdmin->banClient($client['clid'], 0, $punishMessage);
				}
			}
		}
	}
} else {
	die('Error connecting to the server.');
}