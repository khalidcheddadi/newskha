<?php
require 'vendor/autoload.php'; // تأكد من وجود مكتبة Goutte

use Goutte\Client;

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

// دالة لجلب المحتوى من المقال باستخدام Goutte
function fetchContent($url) {
    $client = new Client();
    $crawler = $client->request('GET', $url);
    
    $text = $crawler->filter('p')->each(function ($node) {
        return $node->text();
    });

    // دمج الفقرات في نص واحد
    $content = implode("\n", $text);

    // إزالة أي حقوق طبع ونشر أو نصوص غير مرغوب فيها
    return filterContent(trim($content));
}

// دالة لتصفية المحتوى من النصوص غير المرغوب فيها
function filterContent($text) {
    $patterns = [
        '/© [0-9]{4}/',
        '/All rights reserved/i',
        '/BBC is not responsible/i',
        '/اشترك الآن في النشرة البريدية.+/s',
        '/من شروط النشر:.+/s',
        '/لن يتم نشر عنوان بريدك الإلكتروني.+/s',
        '/مدير النشر.+/s',
        '/العنوان.+/s',
        '/تحميل تطبيق.+/s',
        '/The post .*/i'
    ];

    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }

    return trim(preg_replace('/\n{2,}/', "\n", $text));
}

// دالة لجلب الأخبار من RSS وتخزين الجديدة فقط
function fetchAndStoreNews($url, $conn) {
    $options = array(
        'http'=>array(
            'method'=>"GET",
            'header'=>"User-Agent: Mozilla/5.0\r\n"
        )
    );
    $context = stream_context_create($options);

    $rssContent = @file_get_contents($url, false, $context);
    if ($rssContent === FALSE) {
        echo "فشل في جلب الأخبار من {$url}<br>";
        return;
    }

    $rss = simplexml_load_string($rssContent);
    if ($rss === false) {
        echo "فشل في تحليل الـ RSS من {$url}<br>";
        return;
    }

    foreach ($rss->channel->item as $item) {
        $title = $conn->real_escape_string($item->title);
        $link = $conn->real_escape_string($item->link);
        $description = $conn->real_escape_string($item->description);
        $pubDate = date('Y-m-d H:i:s', strtotime($item->pubDate));
        $imageUrl = '';

        // جلب الصورة إذا كانت موجودة
        $media = $item->children('media', true);
        if ($media->count() > 0) {
            $attributes = $media->attributes();
            if (isset($attributes['url'])) {
                $imageUrl = $conn->real_escape_string($attributes['url']);
            }
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

// جلب الأخبار من المواقع المغربية المختلفة
fetchAndStoreNews('https://www.hespress.com/feed/index.rss', $conn);
fetchAndStoreNews('https://al3omk.com/feed', $conn);
fetchAndStoreNews('https://www.alyaoum24.com/feed', $conn);
fetchAndStoreNews('https://assahraa.ma/rss', $conn);
fetchAndStoreNews('https://www.medias24.com/feed/', $conn);

// جلب الأخبار من المواقع طنجة المختلفة
fetchAndStoreNews('https://tanja24.com/feed', $conn);
fetchAndStoreNews('https://akhbartanger.com/feed', $conn);
fetchAndStoreNews('https://www.tangiernews.com/feed', $conn);
fetchAndStoreNews('https://ahdath.info/category/tanger/feed', $conn);
fetchAndStoreNews('https://www.almaghribia.com/feeds/tanger', $conn);
fetchAndStoreNews('https://www.almaghribtoday.net/rss/المدينة-طنجة', $conn);
fetchAndStoreNews('https://www.koudas.com/feed', $conn);
fetchAndStoreNews('https://www.almaghrebnews.com/feed', $conn);
fetchAndStoreNews('https://www.mstjd.com/feed', $conn);
fetchAndStoreNews('https://www.tangermaroc.com/feed', $conn);

// استعلام لجلب الأخبار المخزنة
$result = $conn->query("SELECT * FROM news ORDER BY pubDate DESC");

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>
