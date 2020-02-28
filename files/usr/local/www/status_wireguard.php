<?php

require("guiconfig.inc");


function pretty_time($value) {
	$time = "";

	$years = (int)($value / (365 * 24 * 60 * 60));
	$value = $value % (365 * 24 * 60 * 60);
	if ($years) {
		$time .= "{$years} year" . ($years > 1 ? "s" : "") . ($value ? ", " : "");
	}

	$days = (int)($value / (24 * 60 * 60));
	$value = $value % (24 * 60 * 60);
	if ($days) {
		$time .= "{$days} day" . ($days > 1 ? "s" : "") . ($value ? ", " : "");
	}

	$hours = (int)($value / (60 * 60));
	$value = $value % (60 * 60);
	if ($hours) {
		$time .= "{$hours} hour" . ($hours > 1 ? "s" : "") . ($value ? ", " : "");
	}

	$minutes = (int)($value / 60);
	if ($minutes) {
		$time .= "{$minutes} minute" . ($minutes > 1 ? "s" : "") . ($value ? ", " : "");
	}

	$seconds = $value % 60;
	if ($seconds) {
		$time .= "{$seconds} second" . ($seconds > 1 ? "s" : "");
	}


	return $time;
}

function ago($value) {
	$now = time();

	if ($now == $value) {
		return "Now";
	} else if ($now < $value) {
		return "?";
	} else {
		return pretty_time($now - $value) . " ago";
	}
}

function bytes($value) {
	if ($value < 1024) {
		return $value . " B";
	} else if ($value < 1024**2) {
		return number_format($value / 1024, 2) . " KiB";
	} else if ($value < 1024**3) {
		return number_format($value / 1024**2, 2) . " MiB";
	} else if ($value < 1024**4) {
		return number_format($value / 1024**3, 2) . " GiB";
	} else {
		return number_format($value / 1024**4, 2) . " TiB";
	}
}

function wireguard_status() {
	$ifname = "tunwg0";
	$status = array();
	exec("wg show {$ifname} dump", $status);

	$output = "";
	for ($i = 0; $i < sizeof($status); $i++) {
		$stats = explode("\t", $status[$i]);
		if ($i == 0) {
			$output .= "<dt>Interface</dt><dd>{$ifname}</dd>";
			$output .= "<dt>Public Key</dt><dd>{$stats[1]}</dd>";
			$output .= "<dt>Listening Port</dt><dd>{$stats[2]}</dd>";
		} else {
			$output .= "<dt>&nbsp;</dt><dd>&nbsp;</dd>";
			$output .= "<dt>Peer</dt><dd>{$stats[0]}</dd>";
			$output .= "<dt>Endpoint</dt><dd>{$stats[2]}</dd>";

			$ips = explode(",", $stats[3]);
			for($j = 0; $j < sizeof($ips); $j++) {
				if ($j == 0) {
					$output .= "<dt>Allowed IPs</dt>";
				} else {
					$output .= "<dt></dt>";
				}
				$output .= "<dd>{$ips[$j]}</dd>";
			}

			if ($stats[4]) {
				$hs = ago($stats[4]);
				$output .= "<dt>Latest handshake</dt><dd>{$hs}</dd>";
			}

			if ($stats[5] || $stats[6]) {
				$tx = bytes($stats[5]);
				$rx = bytes($stats[6]);
				$output .= "<dt>Transfer</dt><dd>{$tx} received</dd>";
				$output .= "<dt></dt><dd>{$rx} sent</dd>";
			}
			if ($stats[7] && $stats[7] <> "off") {
				$ka = pretty_time($stats[7]);
				$output .= "<dt>Persistent keepalive</dt><dd>Every {$ka}</dd>";
			}
		}
	}
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
