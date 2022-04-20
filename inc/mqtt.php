<?php
if(!defined('Functions')){die('You are not authorised to access this');}
require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

try {
	$client = new MqttClient( $mqtt_host, $mqtt_port, $mqtt_client );

	// Create and configure the connection settings as required.
	$connection_settings = ( new ConnectionSettings )
	->setUsername( $mqtt_user )
	->setPassword( $mqtt_pass );

	// Connect to the broker with the configured connection settings and with a clean session.
	$client->connect($connection_settings, true);

} catch ( MqttClientException $e ) {
    // MqttClientException is the base exception of all exceptions in the library. Catching it will catch all MQTT related exceptions.
	echo 'Problem ' . $e;
}
