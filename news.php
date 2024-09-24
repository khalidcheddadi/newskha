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

// دالة لجلب المحتوى من المقال
function fetchContent($url) {
    $options = [
        "http" => [
            "header" => "User-Agent: MyUserAgent/1.0\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    $content = @file_get_contents($url, false, $context);
    if ($content === FALSE) {
        return "فشل في جلب المحتوى.";
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($content);
    
    $text = '';
    $paragraphs = $doc->getElementsByTagName('p');

    foreach ($paragraphs as $paragraph) {
        $text .= $paragraph->nodeValue . "\n";
    }

    // إزالة أي حقوق طبع ونشر أو نصوص غير مرغوب فيها
    $text = filterContent($text);

    return trim($text);
}

// دالة لتصفية المحتوى من النصوص غير المرغوب فيها
function filterContent($text) {
    // إزالة النصوص التي تحتوي على كلمات مثل "حقوق الطبع" أو "©"
    $patterns = [
        '/© [0-9]{4}/', // لإزالة حقوق الطبع التي تبدأ بـ ©
        '/All rights reserved/i', // لإزالة "All rights reserved"
        '/BBC is not responsible/i', // إزالة جمل مثل "BBC is not responsible"
        '/Read about our approach to external linking/i', // جملة نهاية مقالات BBC
    ];

    // تطبيق الأنماط لحذف النصوص
    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }

    // إزالة الفراغات الزائدة والأسطر الفارغة
    $text = trim($text);
    $text = preg_replace('/\n{2,}/', "\n", $text); // إزالة الأسطر الفارغة المتكررة

    return $text;
}


// دالة لجلب الأخبار من RSS وتخزين الجديدة فقط
function fetchAndStoreNews($url, $conn) {
    $rss = simplexml_load_file($url);
    if ($rss === false) {
        return "فشل في جلب الأخبار من {$url}";
    }

    foreach ($rss->channel->item as $item) {
        $title = $conn->real_escape_string($item->title);
        $link = $conn->real_escape_string($item->link);
        $description = $conn->real_escape_string($item->description);
        $pubDate = date('Y-m-d H:i:s', strtotime($item->pubDate));
        $imageUrl = '';

        // جلب الصورة إذا كانت موجودة
        $media = $item->children('media', true);
        if (isset($media->thumbnail)) {
            $imageUrl = $conn->real_escape_string($media->thumbnail->attributes()->url);
        }

        // التحقق مما إذا كان الخبر موجودًا بالفعل
        $sql = "SELECT id FROM news WHERE link='$link'";
        $result = $conn->query($sql);

        if ($result->num_rows == 0) {
            // جلب المحتوى
            $content = fetchContent($link);
            $content = $conn->real_escape_string($content);

            // إدخال البيانات في قاعدة البيانات
            $insert = "INSERT INTO news (title, link, description, pubDate, image, content) VALUES ('$title', '$link', '$description', '$pubDate', '$imageUrl', '$content')";
            if ($conn->query($insert) === TRUE) {
                echo "تم إنشاء سجل جديد: $title<br>";
            } else {
                echo "خطأ: " . $insert . "<br>" . $conn->error;
            }
        }
    }
}

// جلب الأخبار من المواقع المختلفة
fetchAndStoreNews('https://www.reutersagency.com/feed/?taxonomy=best-sectors&post_type=best', $conn);
fetchAndStoreNews('http://feeds.bbci.co.uk/news/rss.xml', $conn);
fetchAndStoreNews('http://rss.cnn.com/rss/edition.rss', $conn);

// استعلام لجلب الأخبار المخزنة
$result = $conn->query("SELECT * FROM news ORDER BY pubDate DESC");

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>


