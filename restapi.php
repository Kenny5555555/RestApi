<?php
header("Content-Type:application/json");
//separate by request method
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET') {
	if (isset($_GET['srch_name']) && $_GET['srch_name']!="") { //Check if search name exists
		include('db.php');
		$srch_name = $_GET['srch_name'];
		//First lets check if the company itself exists
		$result = mysqli_query($connection, "SELECT * FROM `companies` WHERE company_name='$srch_name'");
		if(mysqli_num_rows($result)>0){
			$list = array();
			//Multiple possible results are accepted
			while ($row = mysqli_fetch_array($result)){
				//Checking for daughter companies
				$daughters = mysqli_query($connection, "SELECT * FROM `companies` WHERE parent_name='$srch_name'");
				if(mysqli_num_rows($daughters)>0){
					while ($drow = mysqli_fetch_array($daughters)){
						if(!in_array(array("relationship_type" => "daughter", "org_name" => $drow['company_name']), $list)){
							$list[] = array("relationship_type" => "daughter", "org_name" => $drow['company_name']);
						}
					}
				}
				if(!empty($row['parent_name'])){ //If parent doesn't exist, sisters are also not possible
					//Checking for parents
					$parent = $row['parent_name'];
					$parents = mysqli_query($connection, "SELECT * FROM `companies` WHERE company_name='$parent'");
					if(mysqli_num_rows($parents)>0){
						while ($prow = mysqli_fetch_array($parents)){
							if(!in_array(array("relationship_type" => "parent", "org_name"=> $prow['company_name']), $list)){
								$list[] = array("relationship_type" => "parent", "org_name"=> $prow['company_name']);
							}
						}
					}
					//Checking for sisters
					$sisters = mysqli_query($connection, "SELECT * FROM `companies` WHERE parent_name='$parent'");
					if(mysqli_num_rows($sisters)>0){
						while ($srow = mysqli_fetch_array($sisters)){
							if(!in_array(array("relationship_type" => "sister", "org_name"=> $srow['company_name']), $list) AND $srow['company_name'] != $srch_name){
								$list[] = array("relationship_type" => "sister", "org_name"=> $srow['company_name']);
							}
						}
					}
				}
				
			}
			//Result list sort function to order by name
			function sortByName($a, $b) {
				return $a['org_name'] > $b['org_name'] ? 1 : -1;
			}
			usort($list, 'sortByName');
			
			$listsize = sizeof($list);
			//If there are results for parent,daugters and sisters then list is converted to pages using array chunk method
			if($listsize > 0){
				$limit = 100;
				$page = 1;
				if (isset($_GET['page_nr']) && $_GET['page_nr']!="" && $_GET['page_nr']!= 0 && ctype_digit($_GET['page_nr'])) {
					$page = intval($_GET['page_nr']);
				}
				//Small add-on if page number requested is larger than last page of result, then the last page is used instead
				if($page > $listsize/$limit){
					$page = $listsize/$limit;
				}
				$paged_list = array_chunk($list, $limit, true);
				$response_code = 200;
				$response_desc = "Found List";
				response("GET ", $srch_name, $response_code, $response_desc, $listsize, $page, $paged_list[$page-1]);
			}
			else{
				$response_code = 200;
				$response_desc = "Only company found";
				response("GET ", $srch_name, $response_code, $response_desc, NULL, NULL, NULL);
			}
			
		}else{
			response("GET ", $srch_name, 200,"No Record Found", NULL, NULL, NULL);
			}
		mysqli_close($connection);
	}else{
		response("GET", NULL, 400,"Invalid Request", NULL, NULL, NULL);
		}
}
elseif($method == 'POST'){ //Post function. If array exists it is parsed into a variable nd sent to the insert loop function
	if (isset($_POST['array']) && $_POST['array']!=""){
		parse_str($_POST['array'], $parsed_array);
		include('db.php');
		dbinsert($connection, $parsed_array, NULL);
		response("POST", NULL, 200,"Got POST", NULL, NULL, NULL, $parsed_array);
		mysqli_close($connection);
	}
}
else{ //If not any of the accepted methods
	response(NULL, NULL, 400,"Invalid Request Method", NULL, NULL, NULL);
}

//POST method insert function. Array is sent to function with parent name. First level of array is inserted and on finding daughters the sub-array is resent to the same function
function dbinsert($connection, $array, $parent){
	foreach($array as $element){
		$org_name = $element['org_name'];
		mysqli_query($connection, "INSERT INTO `companies` (company_name, parent_name) VALUES ('$org_name','$parent')");
		if(array_key_exists('daughters', $element)){
			dbinsert($connection, $element['daughters'], $org_name);
		}
	}
}

//all end results use same response function
function response($req_type, $srch_name, $response_code,$response_desc, $listsize, $page, $list, $arr){
	$response['request_type'] = $req_type.$srch_name;
	$response['response_code'] = $response_code;
	$response['response_desc'] = $response_desc;
	if($listsize AND $page AND $list){
		$response['request']['results'] = $listsize;
		$response['request']['page'] = $page;
		$response['request']['list'] = $list;
	}
	if($arr){
		$response['request']['list'] = $arr;
	}
	
	$json_response = json_encode($response);
	echo $json_response;
}
?>