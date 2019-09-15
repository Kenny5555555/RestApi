<?php
header("Content-Type:application/json");
$service_url = ''; //URL to restapi.php
$curl = curl_init($service_url);
$array = array(
		array("org_name" => "Paradise Island", "daughters" => array(
																	array("org_name" => "Banana tree", "daughters" => array(
																															array("org_name" => "Yellow banana"),
																															array("org_name" => "Brown banana"),
																															array("org_name" => "Black banana")
																															)
																		),
																	array("org_name" => "Big banana tree", "daughters" => array(
																															array("org_name" => "Yellow banana"),
																															array("org_name" => "Brown banana"),
																															array("org_name" => "Green banana"),
																															array("org_name" => "Black banana", "daughters" => array(
																																													array("org_name" => "Phoneutria Spider")
																																													)
																																)
																															)
																		)
																	)
			)
);
$curl_post_data = array(
        'array' =>  http_build_query($array)
);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
$curl_response = curl_exec($curl);
if ($curl_response === false) {
    $info = curl_getinfo($curl);
    curl_close($curl);
    die('error occured during curl exec. Additioanl info: ' . var_export($info));
}
curl_close($curl);
$decoded = json_decode($curl_response);
if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
    die('error occured: ' . $decoded->response->errormessage);
}
echo $curl_response;
?>