<?php 
// إعداد الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "news_db";

try {
    // إنشاء اتصال PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // تعيين وضع الخطأ إلى استثناء
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "تم الاتصال بنجاح";
} catch (PDOException $e) {
    echo "فشل الاتصال: " . $e->getMessage();
}


$get_id = $_GET['id'];

$select_nwes = $conn->prepare("SELECT * FROM `news` WHERE id = ? ORDER BY id DESC LIMIT 20");
        $select_nwes->execute([$get_id]);
        if ($select_nwes->rowCount() > 0) {
           while ($fetch_nwes = $select_nwes->fetch(PDO::FETCH_ASSOC)) {
            

 ?>




<form action="" method="POST">

        <img src="./images/<?= $fetch_nwes['image']; ?>" alt="">
        <h5><?= $fetch_nwes['title']; ?></h5>
                <h6><?= $fetch_nwes['content']; ?></h6>
                

                

    </form>

    <?php  
           }}
?>
