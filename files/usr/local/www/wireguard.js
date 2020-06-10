/**
 * Wireguard API callback, trigger from apiGetAsync if/when succesful response received.
 * @param {string} responseText 
 * @param {string} configKey the config item to request
 * @param {string} elementID the HTML element ID to put the retreieved data into.
 * returns true if successful. This is leveraged as the function is recursive.
 */
function apiCallback(responseText, configKey, elementID) {
	elementID = elementID || configKey;
	//console.log(responseText);
	var json = null;
	try {
        json = JSON.parse(responseText);
    }
	catch (e) {
		console.error("Invalid JSON: " + responseText);
        return false;
    }
	if (json && configKey in json && json[configKey].length > 1) {
		if (configKey == "privatekey") { //also need to set the publickey
			if (!apiCallback(responseText, "publickey")) {
				return false; //don't update the private key as the public key update failed.
			}
		}
		document.getElementById(elementID).value = json[configKey];
		document.getElementById(elementID).dispatchEvent(new Event('change', { bubbles: true }));
	}
	else {
		console.error(configKey + " not in JSON: " + responseText);
	}
	return true;
}

/**
 * Query wireguard API and trigger apiCallBack if/when succesful response received.
 * @param {string} configKey the config item to request
 * @param {string} action the action to perform (e.g. get, generate)
 * @param {string} elementID the HTML element ID to put the retreieved data into.
 */
function apiGetAsync(configKey, action, elementID) {
    var xmlHttp = new XMLHttpRequest();
	var url = "/api_wireguard.php?configKey=" + configKey + '&action=' + action;
    xmlHttp.onreadystatechange = function() { 
        if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
            apiCallback(xmlHttp.responseText, configKey, elementID);
    }
    xmlHttp.open("GET", url, true); // true for asynchronous 
    xmlHttp.send(null);
}

/**
 * Create qrcode from makeConf() string. Draw qrcode in "qrcode" div and output conf to "config-div".
 */
var qrcode = null;
function makeCode () {
	var confTxt = makeConf();
	if (confTxt && confTxt.length > 0) {
		if (qrcode == null) {
			document.getElementsByClassName('panel-body')[2].innerHTML = '<div id="config-div"><pre>' + confTxt + '</pre></div><div id="qrcode" style="padding: 15px;"></div>';
			qrcode = new QRCode("qrcode", {
				text : confTxt,
				width : 256, 
				height : 256,
				typeNumber : 4,
				colorDark : "#000000",
				colorLight : "#ffffff",
				correctLevel : QRCode.CorrectLevel.L
			});
			//console.info("new qrcode");
		}
		else {
			document.getElementById('config-div').innerHTML = '<pre>' + confTxt + '</pre>';
			qrcode.makeCode(confTxt);
			//console.info("update qrcode");
		}
	}
  else {
	alert("No text");
  }
}

/**
 * Make peer configuration file from form data. Returns a string with the conf file text.
 */
function makeConf() {
	var conf = "[Interface]\n";
	if (document.getElementById('address').value) {
		conf = conf + "Address = " + (document.getElementById('address').value).split('\n').join(', ') + '\n';
	}
	if (document.getElementById('listenport').value) {
		conf = conf + "ListenPort = " + document.getElementById('listenport').value + '\n';
	}
	if (document.getElementById('dns').value) {
		conf = conf + "DNS = " + document.getElementById('dns').value + '\n';
	}
	if (document.getElementById('privatekey').value) {
		conf = conf + "PrivateKey = " + document.getElementById('privatekey').value + '\n';
	}
	conf = conf + "\n[Peer]\n";
	if (document.getElementById('peer_endpoint_ip').value && document.getElementById('peer_endpoint_port').value) {
		conf = conf + "Endpoint = " + document.getElementById('peer_endpoint_ip').value + ':' + document.getElementById('peer_endpoint_port').value + '\n';
	}
	if (document.getElementById('peer_publickey').value) {
		conf = conf + "PublicKey = " + document.getElementById('peer_publickey').value + '\n';
	}
	if (document.getElementById('peer_allowedips').value) {
		conf = conf + "AllowedIPs = " + (document.getElementById('peer_allowedips').value).split('\n').join(', ') + '\n';
	}
	if (document.getElementById('psk').value) {
		conf = conf + "PresharedKey = " + document.getElementById('psk').value + '\n';
	}
	if (document.getElementById('keepalive').value) {
		conf = conf + "PersistentKeepalive = " + document.getElementById('keepalive').value + '\n';
	}
	return conf;
}

/**
 * Load configuration from query string and generate defaults on the 
 * wireguard_gen_config.xml page.
 */
function wireguard_gen_config() {
	var script = document.createElement("script");
	script.src = "https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js";
	document.head.appendChild(script); 
	document.addEventListener("DOMContentLoaded", function(){
		form = document.getElementsByTagName("form")[0];
		form.onchange = makeCode;
		apiGetAsync("privatekey", "generate");
		if (urlParams.get('name')) {
			document.getElementById('name').value = urlParams.get('name');
		}
		if (urlParams.get('psk')) {
			document.getElementById('psk').value = urlParams.get('psk');
		}
		if (urlParams.get('keepalive')) {
			document.getElementById('keepalive').value = urlParams.get('keepalive');
		}
		if (urlParams.get('endpoint')) {
			var endpoint = urlParams.get('endpoint').split(":");
			if (Array.isArray(endpoint && endpoint.length == 2)) {
				document.getElementById('listenip').value = endpoint[0];
				document.getElementById('listenport').value = endpoint[1];
			}
		}
		
		//document.getElementById('peer_endpoint').value = ":" + (Math.floor(Math.random() * 16383) + 49152); //49152 to 65535
		apiGetAsync("listenport", "get", "peer_endpoint_port");
		apiGetAsync("wan_ip", "get", "peer_endpoint_ip");
		if (urlParams.get('allowedips')) {
			document.getElementById('address').value = urlParams.get('allowedips');
		}
		else {
			apiGetAsync("vpn_address", "generate", "address");
		}
		apiGetAsync("tunnel_ip", "get", "dns");
		apiGetAsync("publickey", "get", "peer_publickey");
		document.getElementById('peer_allowedips').value = '0.0.0.0/0\n::/0';
		document.getElementById('submit').outerHTML = document.getElementById('submit').outerHTML.replace(/button/g, "div");
		document.getElementById('submit').onclick = postToPeer;
	});
}

/**
 * Read the URL query string and return the value of the id parameter
 */
function getQueryId() {
	const urlParams = new URLSearchParams(window.location.search);
	return urlParams.get('id');
}

/**
 * Generate a query string containing the peer configuration.
 * Leveraged by "Create peer conf file" button to pass information
 * to wireguard_gen_config.xml. Returns a query string.
 */
function genPeerQueryStr() {
	query = new URLSearchParams({
		name: document.getElementById('name').value,
		endpoint: document.getElementById('endpoint').value,
		//we don't send publickey as we need to generate a new private/public key pair,
		// as we don't have the peer private key stored.
		//publickey: document.getElementById('publickey').value,
		allowedips: document.getElementById('allowedips').value,
		psk: document.getElementById('psk').value,
		keepalive: document.getElementById('keepalive').value
	});
	return query.toString();
}

/**
 * This function is dedicated to the wireguard_gen_config.xml page
 * It generates a form simulating te form on wireguard_peers.xml, then
 * uses the post function to post is to wireguard_peers.xml.
 * This enables us to generate a conf file for a remote peer, then
 * save the relavant configuration to wireguard_peers on pfSense.
 */
function postToPeer() {
	var url = '/pkg_edit.php?xml=wireguard_peers.xml&act=edit&id=' + getQueryId();
	//There would be benefit in adding error checking before submit...
	
	var endpoint = "";
	if (document.getElementById('listenip').value && document.getElementById('listenport').value)
		endpoint = document.getElementById('listenip').value + ':' + document.getElementById('listenport').value
	
	post(url, {
		__csrf_magic: document.getElementsByName('__csrf_magic')[0].value,
		name: document.getElementById('name').value,
		endpoint: endpoint,
		publickey: document.getElementById('publickey').value,
		allowedips: document.getElementById('address').value,
		psk: document.getElementById('psk').value,
		keepalive: document.getElementById('keepalive').value,
		xml: 'wireguard_peers.xml',
		id: getQueryId(),
		submit: 'Save'
	});
}

/**
 * Sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the paramiters to add to the url
 * @param {string} [method=post] the method to use on the form
 */
function post(path, params, method='post') {
	const form = document.createElement('form');
	form.method = method;
	form.action = path;
	
	//Move the submit function to another variable
	//so that it doesn't get overwritten.
	form._submit_function_ = form.submit;

	for (const key in params) {
		if (params.hasOwnProperty(key)) {
			const hiddenField = document.createElement('input');
			hiddenField.type = 'hidden';
			hiddenField.name = key;
			hiddenField.value = params[key];

			form.appendChild(hiddenField);
		}
	}

	document.body.appendChild(form);
	try {
		form._submit_function_();
	}
	catch(err) {
		console.error(err.message);
		document.body.removeChild(form);
	}
}


/**
 * If we have the wireguard_gen_config.xml page, then preload items and set the page up
 */
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('xml') == 'wireguard_gen_conf.xml') {
	wireguard_gen_config();
}
