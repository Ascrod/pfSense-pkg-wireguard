<?php

require("guiconfig.inc");


function wireguard_status() {
	$status = array();
	exec("wg", $status);

	$output = '<pre>' . implode("\n", $status) . '</pre>';
	return $output;
}

$shortcut_section = "wireguard";
$pgtitle = array(gettext("Status"), "WireGuard");
require_once("head.inc");
?>

<div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">Connection Status</h2></div>
        <div class="panel-body">
	<dl class="dl-horizontal">
	<?php print wireguard_status(); ?>
	</dl>
        </div>
</div>

<?php require_once("foot.inc"); ?>
