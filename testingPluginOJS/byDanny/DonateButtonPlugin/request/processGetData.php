<?php
  require_once '../database/Database.inc.php';
  error_reporting(0);
  try {
    $db = new Database();
    $pdo = $db->getConnection();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $postData = file_get_contents('php://input');
      $postData = json_decode($postData, true);
      if ($postData !== null) {
        // planning add to database
        $response = $postData;
      } else {
        $response['status'] = false;
        $response['data'] = "No data is sent to this method!";
        http_response_code(500);
      }
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $type = $_GET['type'];
      if (isset($type) && $type === "createSmartContract") {
        $id_submission = $_GET['id_submission'];
        
        // Fetch submission data
        $query = "SELECT * FROM submission WHERE id_submission = :id_submission";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id_submission' => $id_submission]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
          $url = 'http://localhost:3000/createSmartContract';
          $curl = curl_init($url);
          curl_setopt($curl, CURLOPT_POST, true);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          
          $headers = [
            "Content-Type: application/x-www-form-urlencoded"
          ];
          curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

          // get from db
          $network = $row["network"];
          $url_api_key = $row["url_api_key"];
          $private_key_account = $row["private_key_account"];

          $data = "network=$network&url_api_key=$url_api_key&private_key_account=$private_key_account";
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
          
          $result = curl_exec($curl);
          http_response_code($httpcode);
          curl_close($curl);
          // address contract address will save/update into db
          // json_decode($result)->data->address_contract
          // example output "0x5030C1E1dFd9B964415869990bC8eE1b56A5E91C"
          $response = json_decode($result);
        } else {
          $response['status'] = false;
          $response['data'] = 'Failure to create smart contract';
          $response['data'] = "id submission not found! ID: $id_submission";
        }
      } else if (isset($type) && $type === "getABIDatabase") {
        $id_submission = $_GET['id_submission'];
        
        // Fetch smart contract data
        $query = "SELECT * FROM smart_contract WHERE id_submission = :id_submission";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id_submission' => $id_submission]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
          $response['status'] = true;
          $response['data'] = "Successfully get ABI data";
          $response['data'] = [
            "address_contract" => $row["smart_contract_address"],
            "abi_json_url" => "http://localhost:3000/abi_json/ABI_FILE_JSON_SMARTCONTRACT.json",
            "expired" => $row["expired"]
          ];
        } else {
          $response['status'] = false;
          $response['data'] = 'Failure to get ABI data';
          $response['data'] = "id submission not found! ID: $id_submission";
        }
      } else if (isset($type) && $type === "getDataDatabase") {
        $id_submission = $_GET['id_submission'];

        // Fetch smart contract data
        $query = "SELECT * FROM smart_contract WHERE id_submission = :id_submission";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id_submission' => $id_submission]);
        $rowPercentages = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rowPercentages) {
          $smart_contract_address = $rowPercentages["smart_contract_address"];

          // Fetch address publishers
          $query = "SELECT wallet_address FROM address_publishers WHERE smart_contract_address LIKE :smart_contract_address";
          $stmt = $pdo->prepare($query);
          $stmt->execute(['smart_contract_address' => "%$smart_contract_address%"]);
          $addressPublishers = $stmt->fetchAll(PDO::FETCH_COLUMN);

          // Fetch address reviewers
          $query = "SELECT wallet_address FROM address_reviewers WHERE smart_contract_address LIKE :smart_contract_address";
          $stmt = $pdo->prepare($query);
          $stmt->execute(['smart_contract_address' => "%$smart_contract_address%"]);
          $addressReviewers = $stmt->fetchAll(PDO::FETCH_COLUMN);

          // Fetch address authors
          $query = "SELECT wallet_address FROM address_authors WHERE smart_contract_address LIKE :smart_contract_address";
          $stmt = $pdo->prepare($query);
          $stmt->execute(['smart_contract_address' => "%$smart_contract_address%"]);
          $addressAuthors = $stmt->fetchAll(PDO::FETCH_COLUMN);

          // Fetch files data
          $query = "SELECT * FROM files WHERE file_id = :file_id";
          $stmt = $pdo->prepare($query);
          $stmt->execute(['file_id' => $id_submission]);
          $rowFiles = $stmt->fetch(PDO::FETCH_ASSOC);

          $response['status'] = true;
          $response['data'] = "Successfully get address data";
          $response['data'] = [
            "publishers" => [
              "percentages" => $rowPercentages["percentages_publisher"],
              "address" => $addressPublishers,
            ],
            "reviewers" => [
              "percentages" => $rowPercentages["percentages_reviewers"],
              "address" => $addressReviewers,
            ],
            "authors" => [
              "percentages" => $rowPercentages["percentages_authors"],
              "address" => $addressAuthors,
            ],
            "documentHash" => basename($rowFiles["path"]),
            "doi" => "b"
          ];
        } else {
          $response['status'] = false;
          $response['data'] = 'Failure to get ABI data';
          $response['data'] = "id submission not found! ID: $id_submission";
        }
      } else {
        $response['status'] = false;
        $response['data'] = "Invalid request params type.";
        http_response_code(500);
      }
    } else {
      $response['status'] = false;
      $response['data'] = "Invalid request method.";
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