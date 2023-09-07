<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("tags", "оборудование для автосервиса, стенды сход-развал, развал схождение, сход развал");
$APPLICATION->SetPageProperty("keywords_inner", "оборудование для автосервиса, стенды сход-развал, развал схождение, сход развал");
$APPLICATION->SetPageProperty("title", "Стенды сход-развал для автосервисов Техно Вектор");
$APPLICATION->SetPageProperty("keywords", "оборудование для автосервиса,  Техно Вектор , Технокар, развал схождение, сход развал, производитель, производитель стенда регулировки Техно  , Techno 2000 Vector , компьютерный стенд регулировки , угол колес , автосервис , Техно , автомобиль , продажа , цена");
$APPLICATION->SetPageProperty("description", "Компьютерные стенды сход-развал на официальном сайте Техно Ветор. Оборудование для автосервисов с доставкой по России. Звоните 8 (800) 505 59 71.");
$APPLICATION->SetTitle("Стенды сход-развал для автосервисов Техно Вектор");
?>


<?$APPLICATION->IncludeComponent("bitrix:catalog.comments","techno",
    array (
        'ELEMENT_ID' => '338',
        'ELEMENT_CODE' => '',
        'IBLOCK_ID' => 4,
        'SHOW_DEACTIVATED' => 'N',
        'URL_TO_COMMENT' => '/local/test/test.php?page=post&blog=comments_blog&post_id=#post_id#&commentId=#commentId#',
        'WIDTH' => '',
        'COMMENTS_COUNT' => '5',
        'BLOG_USE' => 'Y',
        'FB_USE' => '',
        'FB_APP_ID' => '',
        'VK_USE' => '',
        'VK_API_ID' => 'API_ID',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 36000000,
        'CACHE_GROUPS' => 'Y',
        'BLOG_TITLE' => '',
        'BLOG_URL' => 'comments_blog',
        'PATH_TO_SMILE' => '',
        'EMAIL_NOTIFY' => '',
        'AJAX_POST' => 'Y',
        'SHOW_SPAM' => 'Y',
        'SHOW_RATING' => 'N',
        'FB_TITLE' => '',
        'FB_USER_ADMIN_ID' => '',
        'FB_COLORSCHEME' => 'light',
        'FB_ORDER_BY' => 'reverse_time',
        'VK_TITLE' => '',
        'TEMPLATE_THEME' => NULL,
    )
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>