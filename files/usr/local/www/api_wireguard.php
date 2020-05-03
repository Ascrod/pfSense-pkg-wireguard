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

	if (is_array($config['installedpackages']['wireguard']['config'][0])) {
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
	$interfaces = get_configured_interface_with_descr();
	foreach ($interfaces as $ifdescr => $ifname) {
		$ifinfo = get_interface_info($ifdescr);
		if ($ifinfo['hwif'] == 'tunwg0') {
			$int_addr_long = ip2long($ifinfo['ipaddr']);
			$int_subnet_long = ip2long($ifinfo['subnet']);
			//$int_subnet_long = ip2long("255.255.255.248");
			
			//network address e.g. 192.168.1.0
			$net_addr_long = $int_addr_long & $int_subnet_long;
			//broadcast address e.g. 192.168.1.255
			$bc_addr_long = $int_addr_long | (~$int_subnet_long);
			//cidr net mask (not currently used
			$cidr_mask = 32-log(($int_subnet_long ^ ip2long('255.255.255.255'))+1,2);
			
			//note this does not account for /31 and /32 subnets
			$num_useable_ip_addr = pow(2, 32) - ($net_addr_long - $bc_addr_long) - 2;
			$rand_ip_long = 0;
			do {
				$rand_ip_long = $net_addr_long + 1 + rand(0, $num_useable_ip_addr);
			}
			while ($rand_ip_long == $int_addr_long); //repeat if it matches the interface IP. I would be good to build an array of used IPs and exclude them all..
			
			//$response_arr = array($configKey => (long2ip($int_addr_long) . '/' . $cidr_mask . ' ' . long2ip($net_addr_long) . ' ' . long2ip($bc_addr_long) . ' ' . $num_useable_ip_addr) . ' ' . long2ip($rand_ip_long));
			return (long2ip($rand_ip_long) . '/32');
		}
	}
}

wireguard_api();
?>
