<?php

require("guiconfig.inc");
require("/usr/local/pkg/wireguard.inc");
require_once("pfsense-utils.inc"); //required to get interface ip
require_once("util.inc"); //required to get interface ip

function wireguard_api() {
	$response_arr = null;
	$configKey = strtolower(htmlspecialchars($_GET["configKey"]));
	$action = strtolower(htmlspecialchars($_GET["action"]));

	if ($configKey == "wan_ip" && $action == 'get') {
		$response_arr = array($configKey => get_wan_ip());
	}
	elseif ($configKey == "tunnel_ip" && $action == 'get') {
		$response_arr = array($configKey => wireguard_generate_tunnel_ip());
	}
	elseif ($action == 'get') {
		$response_arr = array($configKey => wireguard_get_config($configKey));
	}
	elseif ($configKey == "psk" && $action == 'generate') {
		$response_arr = array('psk' => wireguard_generate_psk());
	}
	elseif ($configKey == "privatekey" && $action == 'generate') {
		$privatekey = wireguard_generate_privkey();
		$publickey = wireguard_generate_pubkey($privatekey);
		$response_arr = array('privatekey' => $privatekey, 'publickey' => $publickey);
	}
	elseif ($configKey == "vpn_address" && $action == 'generate') {
		$response_arr = array($configKey => wireguard_generate_vpn_ip());
	}
	echo json_encode($response_arr);
}

function wireguard_get_config($configKey) {
	global $config;
	$configValue = null;

	if (isset($config['installedpackages']['wireguard']['config'][0][$configKey])) {
		$configValue = 	$config['installedpackages']['wireguard']['config'][0][$configKey];
	}
	if ($configValue && in_array($configKey, array('dns', 'address', 'publickey', 'privatekey', 'preup', 'postup', 'predown', 'postdown', 'allowedips', 'psk'))) {
		$configValue = base64_decode($configValue);
	}
	return $configValue;
}

function get_wan_ip() {
	$interfaces = get_configured_interface_with_descr();
	foreach ($interfaces as $ifdescr => $ifname) {
		$ifinfo = get_interface_info($ifdescr);
		if ($ifname == "WAN" || $ifdescr == 'wan') {
			return $ifinfo['ipaddr'];
		}
	}
}

function wireguard_generate_tunnel_ip() {
	$interfaces = get_configured_interface_with_descr();
	foreach ($interfaces as $ifdescr => $ifname) {
		$ifinfo = get_interface_info($ifdescr);
		if ($ifinfo['hwif'] == 'tunwg0') {
			return $ifinfo['ipaddr'];
		}
	}
}

function wireguard_generate_vpn_ip() {
	global $config;
	if (is_array($config['installedpackages']['wireguard']['config'][0])) {
		$ifconf = $config['installedpackages']['wireguard']['config'][0];
	} else {
		return "";
	}
	$addresses = explode("\r\n", base64_decode($ifconf["address"]));
	
	foreach ($addresses as $address) {
		list($int_addr, $cidr_mask) = explode('/', $address);
		$int_addr_long = ip2long($int_addr);
		$int_subnet_long = ~((1 << (32 - $cidr_mask)) - 1);
		$int_subnet_long = ip2long(long2ip($int_subnet_long));
		
		//network address e.g. 192.168.1.0
		$net_addr_long = $int_addr_long & $int_subnet_long;
		//broadcast address e.g. 192.168.1.255
		$bc_addr_long = $int_addr_long | (~$int_subnet_long);
		//cidr net mask (not currently used)
		$cidr_mask = 32-log(($int_subnet_long ^ ip2long('255.255.255.255'))+1,2);
		
		$rand_ip_long = 0;
		// we need special handling for /31
		if ($int_subnet_long == ip2long("255.255.255.254")) {
			if (long2ip($int_addr_long) == long2ip($bc_addr_long))
				$rand_ip_long = $net_addr_long;
			else
				$rand_ip_long = $bc_addr_long;
		}
		else {
			$num_useable_ip_addr = 253;
			// if it's a /32 then lets just choose an address in the same /24
			if ($int_subnet_long != ip2long("255.255.255.255")) {
				$num_useable_ip_addr = pow(2, 32) - ($net_addr_long - $bc_addr_long) - 2;
			}

			$counter = 0;
			$used_ips = array_merge(array($int_addr_long), get_peer_ips());
			do {
				$rand_ip_long = $net_addr_long + 1 + rand(0, $num_useable_ip_addr);
			}
			while (in_array($rand_ip_long, $used_ips) && ++$counter < ($num_useable_ip_addr * 10)); //repeat if it matches the interface IP. Stops after a number of tries if we haven't found a unique IP
		}

		//return (long2ip($int_addr_long) . '/' . $cidr_mask . ' ' . long2ip($net_addr_long) . ' ' . long2ip($bc_addr_long) . ' ' . $num_useable_ip_addr) . ' ' . long2ip($rand_ip_long);
		return (long2ip($rand_ip_long) . '/32');
	}
}

function get_peer_ips() {
	global $config;
	$peer_ips = array();
	if (!is_array($config['installedpackages']['wireguardpeers']['config'])) {
		return $peer_ips;
	}
	foreach ($config['installedpackages']['wireguardpeers']['config'] as $peerconf) {
		$peer_ips = array_merge($peer_ips, array_map('simple_ip', explode("\r\n", base64_decode($peerconf["allowedips"]))));
	}
	return $peer_ips;
}

//return just the IP address (remove the net mask)
function simple_ip($ip) {
	return ip2long(explode('/', $ip)[0]);
}

wireguard_api();
?>
