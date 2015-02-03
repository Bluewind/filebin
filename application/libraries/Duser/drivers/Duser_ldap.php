<?php
/*
 * Copyright 2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 * Contributions by Hannes Rist
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
class Duser_ldap extends Duser_Driver {
	// none supported
	public $optional_functions = array();

	// Original source: http://code.activestate.com/recipes/101525-ldap-authentication/
	public function login($username, $password) {
		$CI =& get_instance();

		$config = $CI->config->item("auth_ldap");

		if ($username == "" || $password == "") {
			return false;
		}

		$ds = ldap_connect($config['host'],$config['port']);
		if ($ds === false) {
			return false;
		}

		switch ($config["scope"]) {
			case "base":
				$r = ldap_read($ds, $config['basedn'], $config["username_field"].'='.$username);
				break;
			case "one":
				$r = ldap_list($ds, $config['basedn'], $config["username_field"].'='.$username);
				break;
			case "subtree":
				$r = ldap_search($ds, $config['basedn'], $config["username_field"].'='.$username);
				break;
			default:
				throw new \exceptions\ApiException("libraries/duser/ldap/invalid-ldap-scope", "Invalid LDAP scope");
		}
		if ($r === false) {
			return false;
		}

		foreach ($config["options"] as $key => $value) {
			if (ldap_set_option($ds, $key, $value) === false) {
				return false;
			}
		}

		$result = ldap_get_entries($ds, $r);
		if ($result === false || !isset($result[0])) {
			return false;
		}

		// ignore errors from ldap_bind as it will throw an error if the password is incorrect
		if (@ldap_bind($ds, $result[0]['dn'], $password)) {
			ldap_unbind($ds);
			return array(
				"username" => $result[0][$config["username_field"]][0],
				"userid" => $result[0][$config["userid_field"]][0]
			);
		}

		return false;
	}
}
