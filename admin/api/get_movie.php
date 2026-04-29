<?php
include("../connect.php");

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid movie ID']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $con->prepare("SELECT * FROM movies WHERE movieid = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($movie = $result->fetch_assoc()) {
    echo json_encode($movie);
} else {
    echo json_encode(['error' => 'Movie not found']);
}
?>