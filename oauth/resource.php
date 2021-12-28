<?php
/**
 * @author Denis CLAVIER <clavierd at gmail dot com>
 * Adapted from Oauth2-server-php cookbook
 * @see http://bshaffer.github.io/oauth2-server-php-docs/cookbook/
 */

// include our OAuth2 Server object
require_once __DIR__.'/server.php';

// include our LDAP object
require_once __DIR__.'/LDAP/LDAP.php';
require_once __DIR__.'/LDAP/config_ldap.php';

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();
    die;
}

// set default error message
$resp = array("error" => "Unknown error", "message" => "An unknown error has occured, please report this bug");

// get information on user associated to the token
$info_oauth = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());
$user = $info_oauth["user_id"];
$assoc_id = intval($info_oauth["assoc_id"]);

// Open a LDAP connection
$ldap = new LDAP($ldap_host, $ldap_port, $ldap_version, $ldap_start_tls);

// Try to get user data on the LDAP
try {
    $data = $ldap->getDataForMattermost($ldap_base_dn, $ldap_filter, $ldap_bind_dn, $ldap_bind_pass, $ldap_search_attribute, $user);
	$username = explode('@', $data['mail']);
	$resp = array("name" => $data['name'],"username" => $username[0],"id" => (int)$assoc_id,"state" => "active","email" => $data['mail'],"login" => $username[0]);

    // Below is the old version, still consistent with Mattermost before version 4.4
    // $resp = array("name" => $data['cn'],"username" => $user,"id" => $assoc_id,"state" => "active","email" => $data['mail']);
} catch (Exception $e) {
    if ($e->getCode() == 404) {
	$resp = [
	    "error" => "User not found",
	    "message" => "$user is not in the group of authorized users."
	];
    } else {
	$resp = array(
	    "error" => "Impossible to get data",
	    "message" => $e->getMessage()
	);
    }
}

// send data or error message in JSON format
echo json_encode($resp);
