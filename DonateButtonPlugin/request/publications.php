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
            $postData = file_get_contents('php://input');
            $postData = json_decode($postData, true);

            $submissionId = $postData['submissionId'];
            $agreement = $postData['agreement'];

            $query = "UPDATE publications SET author_agreement = :authorAgreement WHERE submission_id = :submissionId";
            $statement = $pdo->prepare($query);
            $statement->bindParam(':authorAgreement', $agreement);
            $statement->bindParam(':submissionId', $submissionId);
            $exec = $statement->execute();

            if ($exec) {
                $response['data'] = 'Update successful';
            } else {
                $response['data'] = 'Update failed';
            }
        } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (isset($_GET['submissionId'])) {
                $submissionId = $_GET['submissionId'];

                $query = "SELECT * FROM publications WHERE submission_id = '$submissionId'";
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
