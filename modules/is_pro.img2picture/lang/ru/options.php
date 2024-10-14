<?
$MESS['ISPRO_IMG2PICTURE_TAB_SET_DESC'] = 'Описание';
$MESS['ISPRO_IMG2PICTURE_TAB_TITLE_DESC'] = 'Описание модуля';

$MESS['ISPRO_IMG2PICTURE_TAB_SET_IMGCONVERT'] = 'Сжатие изображений';
$MESS['ISPRO_IMG2PICTURE_TAB_TITLE_IMGCONVERT'] = 'Сжатие оригиналов изображений';

$MESS['ISPRO_IMG2PICTURE_TAB_SET_OPTION'] = 'Настройки';
$MESS['ISPRO_IMG2PICTURE_TAB_TITLE_OPTION'] = 'Настройки Img2Picture';
$MESS['ISPRO_IMG2PICTURE_RESPONSIVE'] = 'Адаптив';
$MESS['ISPRO_IMG2PICTURE_MIN_SCREEN_WIDTH'] = 'Минимальная ширина экрана экрана (min-width), px';
$MESS['ISPRO_IMG2PICTURE_MAX_SCREEN_WIDTH'] = 'Максимальная ширина экрана экрана (max-width), px';
$MESS['ISPRO_IMG2PICTURE_MAX_IMG_WIDTH'] = 'Ширина изображения px';

$MESS['ISPRO_IMG2PICTURE_USE_WEBP'] = 'Применить конвертацию изображений в webp';
$MESS['ISPRO_IMG2PICTURE_USE_AVIF'] = 'Применить конвертацию изображений в avif';
$MESS['ISPRO_IMG2PICTURE_USE_ONLY_WEBP_AVIF'] = 'Использовать только современные форматы (webp и avif)';

$MESS['ISPRO_IMG2PICTURE_USE_IMAGICK'] = 'Использовать Imagick (Если версия php меньше 8.1, или не формируются avif файлы)';
if (!class_exists('Imagick')) {
	$MESS['ISPRO_IMG2PICTURE_USE_IMAGICK'] .= ' <b>(Не применимо, так как не утановлен Imagick)</b>';
};

$MESS['ISPRO_IMG2PICTURE_LAZYLOAD'] = 'Использовать ленивую загрузку изображений (lazyload)';
$MESS['ISPRO_IMG2PICTURE_ATTR_SRC'] = 'В каких аттрибутах тега img искать ссылку на изображение *<br> (по умолчанию src)';
$MESS['ISPRO_IMG2PICTURE_ATTR_SRC_ERROR'] = 'Поле является обязательным для заполнения (по умолчанию "src")';
$MESS['ISPRO_IMG2PICTURE_CACHE_TTL'] = 'Время хранения кеша<br>(по умолчанию 2592000 - 30 дней)';



$MESS['ISPRO_IMG2PICTURE_BACKGROUNDS'] = 'Обрабатывать изображения в style="background..."';
$MESS['ISPRO_IMG2PICTURE_TAGS_ATTR'] = 'Обрабатывать изображения в аттрибутах тегов:<br/>{тег}:{Аттрибут с изображением}<br>a:href';
$MESS['ISPRO_IMG2PICTURE_IMG_COMPRESSION'] = 'Качество сохряняемых изображений (0-100)';
$MESS['ISPRO_IMG2PICTURE_EXCEPTIONS_DIR'] = 'Исключения:<br>каталоги (директории) сайта, где модуль не будет работать';
$MESS['ISPRO_IMG2PICTURE_EXCEPTIONS_SRC'] = 'Исключения:<br>регулярные выражения по аттрибуту src на изображения, которые не надо преобразовывать';
$MESS['ISPRO_IMG2PICTURE_EXCEPTIONS_TAG'] = 'Исключения:<br>регулярные выражения по тегу img целиком, которые не надо преобразовывать';

$MESS['ISPRO_IMG2PICTURE_MODULE_MODE'] = 'Режим работы модуля';
$MESS['ISPRO_IMG2PICTURE_MODULE_MODE_off'] = 'Выключен';
$MESS['ISPRO_IMG2PICTURE_MODULE_MODE_test'] = 'Тестирование';
$MESS['ISPRO_IMG2PICTURE_MODULE_MODE_on'] = 'Включен';

$jsPath   = str_replace(
	[$_SERVER['DOCUMENT_ROOT'], 'lang/ru'],
	['', 'lib/js/'],
	__DIR__
);

$MESS['ISPRO_IMG2PICTURE_CUSTOM_JS'] = "Не подключать JS в модуле<br/>
<pre style=\"overflow-x: scroll; max-width: 200px\" >
{$jsPath}lozad.min.js
{$jsPath}img2picture.min.js
</pre>
";

$MESS['ISPRO_IMG2PICTURE_COMPATIBLE_MODE'] = 'Режим совместимости<br>(используюстя старый вызов событий для изменения $arResult)';
$MESS['ISPRO_IMG2PICTURE_ADD_WIDTH'] = 'Добавлять аттрибут width оригинального изображения';
$MESS['ISPRO_IMG2PICTURE_ADD_HEIGHT'] = 'Добавлять аттрибут height оригинального изображения';

$MESS['ISPRO_IMG2PICTURE_SAVE'] = 'Сохранить';
$MESS['ISPRO_IMG2PICTURE_DEFAULT'] = 'Сбросить все настройки по умолчанию';
$MESS['ISPRO_IMG2PICTURE_REMOVE_FILES'] = 'Отчистить кеш модуля и созданные файлы';

$MESS['ISPRO_IMG2PICTURE_INFO'] = '
<h3>Как это работает</h3>
<p>
Модуль заменяет теги img на теги picture с ресайзом и конвертацией изображений.
<br>
Модуль так-же заменяет background с ресайзом и конвертацией изображений.
<br>
Модуль не работает если текущий пользователь адмиристратор - для того чтобы не заменять теги во время редактирования контента.
<br>
Модуль не работает в администаривной части сайта.
<br>
Т.е. тестирование работы модуля надо проводить НЕ авторизованным пользователем в режиме работы модуля "тестирование".

</p>

<h3>В режиме "Тестирование":</h3>
<p>
<b>Тестирование работы модуля надо проводить НЕ авторизованным пользователем в режиме работы модуля "тестирование".</b>
<br>
включить модуль дописав get параметр ?img2picture=on<br>
выключить модуль дописав get параметр ?img2picture=off<br>
лог пишется в стандартный файл битрикса (по умолчанию /__bx_log.log)<br>
</p>

<h3>Доступные функции:</h3>
<p>
Для использования функций включите модуль
<pre>
CModule::IncludeModule("is_pro.img2picture");
</pre>
</p>
<hr>
<p>
<pre>
IS_PRO\img2picture\Cimg2picture::doIt(string $content, array $option = [])
</pre>
вернет контент с замененными img на picture<br>
$content - контент в котором необходимо заменить все img<br>
$option - необязательный массив параметров замены
</p>
<hr>
<p>
<pre>
IS_PRO\img2picture\Cimg2picture::MakeWebp(string $src, array $option = []);
IS_PRO\img2picture\Cimg2picture::MakeAvif(string $src, array $option = []);
</pre>
вернет ссылку на созданный webp/avif соответвенное<br>
в случае не удачи вернет false<br>
$src - ссылка на изображение<br>
$option - необязательный массив параметров замены
</p>
<hr>
<p>
<pre>
IS_PRO\img2picture\Cimg2picture::GetOptions()
</pre>
вернет параметры модуля
</p>

<h3>Доступные события модуля:</h3>
<p>
Можно перехватить и кастомизировать замену
</p>
<pre>
/* Для перехвата замены img */
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler("is_pro.img2picture", "OnPrepareResultImg", "MyPicture");

function MyPicture(\Bitrix\Main\Event $event)
{
	$arParam = $event->getParameters();
	$arResult = &$arParam[0];
	/* Какой-то код меняющий $arResult */
}

/* Для перехвата замены backgropunds */
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler("is_pro.img2picture", "OnPrepareResultBackground", "MyBackground");

function MyBackground(\Bitrix\Main\Event $event)
{
	$arParam = $event->getParameters();
	$arResult = &$arParam[0];
	/* Какой-то код меняющий $arResult */
}

/* Для перехвата замены значений аттрибутов тегов */
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler("is_pro.img2picture", "OnPrepareResultTagsAttr", "MyTagsAttr");

function MyTagsAttr(\Bitrix\Main\Event $event)
{
	$arParam = $event->getParameters();
	$arResult = &$arParam[0];
	/* Какой-то код меняющий $arResult */
}
</pre>
<p>
$arResult["place"] - содержит результирующий тег
</p>

<h2>Управление модулем на странице</h2>
<p>Иногда надо обновить кеш только на конкретной странице или посмотреть почему что-то неправильно конвертируется</p>
<p><b>Вы должны быть НЕ авторизованы</b></p>
<p>включить отладку на странице ?img2pictureDebug=Y</p>
<p>отчистить кеш и переконвертировать изображений на странице ?img2pictureClearCache=Y</p>
<p>отчистить кеш и переконвертировать отдельного изображения на странице ?img2pictureClearCache=[SRC картинки]</p>


<h2>Поблагодарить</h2>
<iframe src="https://yoomoney.ru/quickpay/shop-widget?writer=seller&default-sum=1000&button-text=11&payment-type-choice=on&mobile-payment-type-choice=on&successURL=&quickpay=shop&account=410011713559173&targets=Перевод%20по%20кнопке&"></iframe>

';
