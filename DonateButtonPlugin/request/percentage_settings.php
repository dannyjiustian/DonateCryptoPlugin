<?php

require_once '../database/Database.inc.php';

$response = array(
    'success' => true,
    'data' => ""
);

try {
    $db = new Database();
    $pdo = $db->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $percentage = file_get_contents('php://input');
        $percentage = json_decode($percentage, true);

        $percentage_authors = $percentage['percentage_authors'];
        $percentage_reviewers = $percentage['percentage_reviewers'];
        $percentage_publisher = $percentage['percentage_publisher'];

        $query = "UPDATE percentage_settings SET percentage_authors = :percentage_authors, percentage_reviewers = :percentage_reviewers, percentage_publisher = :percentage_publisher WHERE id = 1";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':percentage_authors', $percentage_authors);
        $statement->bindParam(':percentage_reviewers', $percentage_reviewers);
        $statement->bindParam(':percentage_publisher', $percentage_publisher);
        $exec = $statement->execute();

        if ($exec) {
            $response['success'] = true;
            $response['data'] = 'Update successful';
        } else {
            $response['success'] = false;
            $response['data'] = 'Update failed';
        }
        // $response['data'] = $percentage['percentage_authors'];
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $query = "SELECT * FROM percentage_settings";
        $exec = $pdo->query($query);

        $row = $exec->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['data'] = $row;
    } else {
        $response['success'] = false;
        $response['data'] = "Invalid request method.";
    }
} catch (Exception $e) {
    throw new Exception($e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
