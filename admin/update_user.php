<?php
include("connect.php");

$userid = $_POST['userid'];
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone_number'];
$role = $_POST['roletype'];

$sql = "UPDATE users SET name=?, email=?, phone_number=?, roletype=? WHERE userid=?";
$stmt = $con->prepare($sql);
$stmt->bind_param("sssii", $name, $email, $phone, $role, $userid);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>