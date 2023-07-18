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

        $publisher_id = $percentage['publisher_id'];
        $percentage_authors = $percentage['percentage_authors'];
        $percentage_reviewers = $percentage['percentage_reviewers'];
        $percentage_publisher = $percentage['percentage_publisher'];
        $percentage_editors = $percentage['percentage_editors'];

        // Check if the table `percentage_settings` already has the publisher_id
        $query = "SELECT * FROM percentage_settings WHERE publisher_id = :publisher_id";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':publisher_id', $publisher_id);
        $statement->execute();
        $row = $statement->fetch();

        if ($row) {
            // Update the record
            $query = "UPDATE percentage_settings SET percentage_authors = :percentage_authors, percentage_reviewers = :percentage_reviewers, percentage_publisher = :percentage_publisher, percentage_editors = :percentage_editors WHERE publisher_id = :publisher_id";
            $statement = $pdo->prepare($query);
            $statement->bindParam(':percentage_authors', $percentage_authors);
            $statement->bindParam(':percentage_reviewers', $percentage_reviewers);
            $statement->bindParam(':percentage_publisher', $percentage_publisher);
            $statement->bindParam(':percentage_editors', $percentage_editors);
            $statement->bindParam(':publisher_id', $publisher_id);
            $exec = $statement->execute();
        } else {
            // Add a new record
            $query = "INSERT INTO percentage_settings (publisher_id, percentage_authors, percentage_reviewers, percentage_publisher, percentage_editors) VALUES (:publisher_id, :percentage_authors, :percentage_reviewers, :percentage_publisher, :percentage_editors)";
            $statement = $pdo->prepare($query);
            $statement->bindParam(':publisher_id', $publisher_id);
            $statement->bindParam(':percentage_authors', $percentage_authors);
            $statement->bindParam(':percentage_reviewers', $percentage_reviewers);
            $statement->bindParam(':percentage_publisher', $percentage_publisher);
            $statement->bindParam(':percentage_editors', $percentage_editors);
            $exec = $statement->execute();
        }

        if ($exec) {
            $response['success'] = true;
            $response['data'] = 'Update successful';
        } else {
            $response['success'] = false;
            $response['data'] = 'Update failed';
        }
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $publisher_id = $_GET['publisher_id'];
        $query = "SELECT * FROM percentage_settings WHERE publisher_id = $publisher_id";
        $exec = $pdo->query($query);

        $row = $exec->fetchAll(PDO::FETCH_ASSOC);

        if ($row) {
            $response['success'] = true;
            $response['data'] = $row;
        } else {
            $response['success'] = false;
            $response['data'] = 'No publisher data found for id : ' . $publisher_id;
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
