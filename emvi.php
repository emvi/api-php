<?php
class EmviClientConfig {
	private $authHost = "https://auth.emvi.com";
	private $apiHost = "https://api.emvi.com";

	function setAuthHost($authHost) {
		$this->authHost = $authHost;
	}

	function getAuthHost() {
		return $this->authHost;
	}

	function setAPIHost($apiHost) {
		$this->apiHost = $apiHost;
	}

	function getAPIHost() {
		return $this->apiHost;
	}
}

class EmviClient {
	private $clientId;
	private $clientSecret;
	private $organization;
	private $config;

	function __construct($clientId, $clientSecret, $organization, $config = null) {
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->organization = $organization;

		if(!is_null($config)) {
			$this->config = $config;
		}
		else {
			$this->config = new EmviClientConfig();
		}
	}

	function refreshToken() {
		$url = $this->config->getAuthHost()."/api/v1/auth/token";
		$data = array(
			"grant_type" => "client_credentials",
			"client_id" => $this->clientId,
			"client_secret" => $this->clientSecret
		);
		$options = array(
		    "http" => array(
		        "method"  => "POST",
		        "header" => "Content-Type: application/x-www-form-urlencoded\r\n",
		        "content" => json_encode($data)
		    )
		);
		$context  = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);
		
		if($result === FALSE) {
			throw new Exception("Error refreshing token");
		}

		$resp = json_decode($result);
		$_SESSION["emvi_access_token"] = $resp->access_token;
	}

	function getArticle($id, $langId = "", $version = 0, $retry = true) {
		$url = $this->config->getAPIHost()."/api/v1/article/".$id."?lang=".$langId."&version=".$version;
		$options = array(
		    "http" => array(
		        "method"  => "GET",
		        "header" => $this->getHeader()
		    )
		);
		$context  = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);
		
		if($result === FALSE) {
			if($this->isUnauthorized($http_response_header[0]) && $retry) {
				$this->refreshToken();
				return $this->getArticle($id, $langId, $version, false);
			}
			else {
				throw new Exception("Error reading article");
			}
		}

		return json_decode($result);
	}

	private function getHeader() {
		$token = "";

		if(isset($_SESSION["emvi_access_token"])) {
			$token = $_SESSION["emvi_access_token"];
		}

		return "Authorization: Bearer ".$token."\r\n".
			"Organization: ".$this->organization."\r\n".
			"Client: ".$this->clientId."\r\n".
			"Content-Type: application/json\r\n";
	}

	private function isUnauthorized($header) {
		return strpos($header, "401") !== FALSE;
	}
}
?>
