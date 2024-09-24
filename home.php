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
            "header" => "User-Agent: Mozilla/5.0\r\n"
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
        '/اشترك الآن في النشرة البريدية.+/s', // لإزالة نص الاشتراك في النشرة البريدية
        '/من شروط النشر:.+/s', // لإزالة شروط النشر
        '/لن يتم نشر عنوان بريدك الإلكتروني.+/s', // إزالة نص البريد الإلكتروني
        '/مدير النشر.+/s', // إزالة اسم مدير النشر
        '/العنوان.+/s', // إزالة عنوان النشر
        '/Votre adresse e-mail ne sera pas publiée.+/s', // إزالة النصوص الفرنسية
        '/Nom \*/', // إزالة اسم التعليق باللغة الفرنسية
        '/E-mail \*/', // إزالة البريد الإلكتروني باللغة الفرنسية
        '/Commentaire \*/', // إزالة تعليق باللغة الفرنسية
        '/تحميل تطبيق.+/s', // إزالة نصوص عربية أخرى
        '/The post .*/i' // إزالة النص الذي يبدأ بـ "The post" ويليه أي نص
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
