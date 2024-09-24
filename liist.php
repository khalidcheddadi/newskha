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

// استعلام لجلب الأخبار المخزنة
$result = $conn->query("SELECT * FROM news ORDER BY pubDate DESC");

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الأخبار</title>
</head>
<body>
    <h1>الأخبار</h1>
    <table border="1">
        <thead>
            <tr>
                <th>العنوان</th>
                <th>الرابط</th>
                <th>الوصف</th>
                <th>تاريخ النشر</th>
                <th>الصورة</th>
                <th>المحتوى</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['title']; ?></td>
                        <td><a href="<?php echo $row['link']; ?>" target="_blank">رابط</a></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['pubDate']; ?></td>
                        <td>
                            <?php if (!empty($row['image'])): ?>
                                <img src="<?php echo $row['image']; ?>" alt="صورة" width="100">
                            <?php endif; ?>
                        </td>
                        <td><?php echo nl2br($row['content']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">لا توجد أخبار متاحة.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
