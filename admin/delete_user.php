<?php
include("connect.php");

$id = $_POST['id'];

$stmt = $con->prepare("DELETE FROM users WHERE userid=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
?>