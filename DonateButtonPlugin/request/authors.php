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
        $authorData = file_get_contents('php://input');
        $authorData = json_decode($authorData, true);

        $author_id = $authorData['author_id'];
        $crypto_wallet_address = $authorData['crypto_wallet_address'];
        $email = $authorData['email'];
        $publication_id = $authorData['publication_id'];
        $percentage = $authorData['percentage'];
        $type = $authorData['type'];

        if ($type == "update_wallet") {
            if ($author_id != null) {
                $query = "UPDATE authors SET crypto_wallet_address = :walletAddress WHERE author_id = :author_id";
                $statement = $pdo->prepare($query);
                $statement->bindParam(':walletAddress', $crypto_wallet_address);
                $statement->bindParam(':author_id', $author_id);
                $exec = $statement->execute();

                if ($exec) {
                    $response['data'] = 'Update successful';
                } else {
                    $response['data'] = 'Update failed';
                }
            }
        } else if ($type == "update_percentage") {
            $query = "UPDATE authors SET percentage = :percentage WHERE author_id = :author_id";
            $statement = $pdo->prepare($query);
            $statement->bindParam(':percentage', $percentage);
            $statement->bindParam(':author_id', $author_id);
            $exec = $statement->execute();

            if ($exec) {
                $response['data'] = 'Update successful';
            } else {
                $response['data'] = 'Update failed';
            }
        }
        if ($author_id == null) {
            $query = "UPDATE authors SET crypto_wallet_address = :walletAddress WHERE email = :email AND publication_id = :publication_id";
            $statement = $pdo->prepare($query);
            $statement->bindParam(':walletAddress', $crypto_wallet_address);
            $statement->bindParam(':email', $email);
            $statement->bindParam(':publication_id', $publication_id);
            $exec = $statement->execute();

            if ($exec) {
                $response['data'] = 'Update successful in update';
            } else {
                $response['data'] = 'Update failed';
            }
        }

        // $response['data'] = $author_id;
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $type = $_GET['type'];
        if (isset($type) &&  $type === 'getById') {
            if (isset($_GET['author_id'])) {
                $author_id = $_GET['author_id'];

                $query = "SELECT * FROM authors WHERE author_id = '$author_id'";
                $exec = $pdo->query($query);

                $row = $exec->fetchAll(PDO::FETCH_ASSOC);

                $response['data'] = $row;
            }
        } else if (isset($type) &&  $type === 'getAllAuthorData') {
            if (isset($_GET['submission_id'])) {
                $submission_id = $_GET['submission_id'];

                $query = "SELECT * FROM authors WHERE publication_id = '$submission_id'";
                $exec = $pdo->query($query);

                $row = $exec->fetchAll(PDO::FETCH_ASSOC);

                $response['data'] = $row;
            }
        } else if (isset($type) &&  $type === 'getAuthorSettings') {
            if (isset($_GET['author_id'])) {
                $author_id = $_GET['author_id'];

                $query = "SELECT * FROM author_settings WHERE author_id = '$author_id'";
                $exec = $pdo->query($query);

                $row = $exec->fetchAll(PDO::FETCH_ASSOC);

                $response['data'] = $row;
            }
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
