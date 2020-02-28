<?php

global $priv_list;

$priv_list['page-vpn-wireguard'] = array();
$priv_list['page-vpn-wireguard']['name'] = "WebCfg - VPN: WireGuard";
$priv_list['page-vpn-wireguard']['descr'] = "Allow access to the 'VPN: WireGuard' page.";
$priv_list['page-vpn-wireguard']['match'] = array();

$priv_list['page-vpn-wireguard']['match'][] = "pkg.php?xml=wireguard.xml*";
$priv_list['page-vpn-wireguard']['match'][] = "pkg.php?xml=wireguard_peers.xml*";
$priv_list['page-vpn-wireguard']['match'][] = "pkg_edit.php?xml=wireguard.xml*";
$priv_list['page-vpn-wireguard']['match'][] = "pkg_edit.php?xml=wireguard_peers.xml*";
$priv_list['page-vpn-wireguard']['match'][] = "status_wireguard.php*";

?>
