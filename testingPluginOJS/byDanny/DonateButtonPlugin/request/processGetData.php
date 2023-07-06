<?php
  try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $postData = file_get_contents('php://input');
      $postData = json_decode($postData, true);
      if ($postData !== null) {
        // add to database
        $response = $postData;
      } else {
        $response['status'] = false;
        $response['message'] = "No data is sent to this method!";
        http_response_code(500);
      }
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $type = $_GET['type'];
      if (isset($type) && $type === "createSmartContract") {
        $url = 'http://localhost:3000/createSmartContract';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        $headers = [
          "Content-Type: application/x-www-form-urlencoded"
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        // get from db
        $network = "sepolia";
        $url_api_key = "https://eth-sepolia.g.alchemy.com/v2/poW824z7baY51XHHw5_9oqfNZo7Mcnav";
        $private_key_account = "dfa5e75b3dbcc8e3b928e5723dab7ce657c620cac73bd991dbd8268aaac55a18";

        $data = "network=$network&url_api_key=$url_api_key&private_key_account=$private_key_account";
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        $result = curl_exec($curl);
        http_response_code($httpcode);
        curl_close($curl);
        $response = json_decode($result);
      } else if (isset($type) && $type === "getABIDatabase") {
        $response['status'] = true;
        $response['message'] = "Successfuly get abi data";
        $response['data'] = [
          "address_contract" => "0xfd461D0E11E2AB6578B1fB0861792CCE8F5Dcc77", // get from db
          "abi_json_url" => "http://localhost:3000/abi_json/ABI_FILE_JSON_SMARTCONTRACT.json", // not change
          "expired" => "2023-07-10T10:02:41+0000" // get from db
        ];
      } else if (isset($type) && $type === "getDataDatabase") {
        $response['status'] = true;
        $response['message'] = "Successfuly get address data";
        $response['data'] = [
          "publishers" => [
            "percentages" => 30,
            "address" => ["0x4D43B400eF65Cc48Ef68895b73239d6b981a56B3"],
          ],
          "reviewers" => [
            "percentages" => 10,
            "address" => ["0x4F308f137Bf030a016c4C903A119844b0E5B2F86"],
          ],
          "authors" => [
            "percentages" => 60,
            "address" => [
              "0x7e37355904356EfE4172cBd4df6cf0BF1f92C24E",
              "0x005d1822042698732F1B639C98B4cD269B403716",
            ],
          ],
          "documentHash" => "a",
          "doi" => "b"
        ];
      } else {
        $response['status'] = false;
        $response['message'] = "Invalid request params type.";
        http_response_code(500);
      }
    } else {
      $response['status'] = false;
      $response['message'] = "Invalid request method.";
      http_response_code(500);
    }
  } catch (Exception $e) {
    $response['status'] = false;
    $response['message'] = "Invalid request method.";
    $response['data'] = $e->getMessage();
    http_response_code(500);
  }

  header('Content-Type: application/json');
  echo json_encode($response);
?>