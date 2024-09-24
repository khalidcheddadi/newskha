<?php
// إعداد الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root"; // اسم المستخدم
$password = ""; // كلمة مرور قاعدة البيانات
$dbname = "news_db";

// الاتصال بقاعدة البيانات
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على البيانات من الطلب
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من وجود البيانات
if (!$data) {
    echo json_encode(["success" => false, "error" => "No data received."]);
    exit;
}

$title = $conn->real_escape_string($data['title']);
$link = $conn->real_escape_string($data['link']);
$description = $conn->real_escape_string($data['description']);
$pubDate = $conn->real_escape_string($data['pubDate']);
$imageUrl = $conn->real_escape_string($data['imageUrl']);

// إدخال البيانات في قاعدة البيانات
$insert = "INSERT INTO news (title, link, description, pubDate, image) VALUES ('$title', '$link', '$description', '$pubDate', '$imageUrl')";

if ($conn->query($insert) === TRUE) {
    echo json_encode(["success" => true]);
} else {
    error_log("Error: " . $insert . " - " . $conn->error); // تسجيل الأخطاء
    echo json_encode(["success" => false, "error" => $conn->error]);
}

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>
