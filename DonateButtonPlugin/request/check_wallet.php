<?php

require_once '../database/Database.inc.php';

$response = array(
    'success' => true,
    'data' => ""
);

try {
    $db = new Database();
    $pdo = $db->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($_GET['publication_id'])) {
            $publication_id = $_GET['publication_id'];

            $query = "SELECT * FROM authors WHERE publication_id = '$publication_id'";
            $exec = $pdo->query($query);

            $row = $exec->fetchAll(PDO::FETCH_ASSOC);

            $response['data'] = $row;
        }
    } else {
        $response['success'] = false;
        $response['data'] = "Invalid request method.";
    }
} catch (Exception $e) {
    throw new Exception($e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
