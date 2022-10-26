<?
$MESS['ISPRO_IMG2PICTURE_TAB_SET_DESC'] = 'Описание';
$MESS['ISPRO_IMG2PICTURE_TAB_TITLE_DESC'] = 'Описание модуля';

$MESS['ISPRO_IMG2PICTURE_TAB_SET_OPTION'] = 'Настройки';
$MESS['ISPRO_IMG2PICTURE_TAB_TITLE_OPTION'] = 'Настройки Img2Picture';
$MESS['ISPRO_IMG2PICTURE_RESPONSIVE'] = 'Адаптив';
$MESS['ISPRO_IMG2PICTURE_MIN_SCREEN_WIDTH'] = 'Минимальная ширина экрана экрана (min-width), px';
$MESS['ISPRO_IMG2PICTURE_MAX_SCREEN_WIDTH'] = 'Максимальная ширина экрана экрана (max-width), px';
$MESS['ISPRO_IMG2PICTURE_MAX_IMG_WIDTH'] = 'Ширина изображения px';

$MESS['ISPRO_IMG2PICTURE_USE_WEBP'] = 'Применить конвертацию изображений в webp';
$MESS['ISPRO_IMG2PICTURE_LAZYLOAD'] = 'Использовать ленивую загрузку изображений (lazyload)';
$MESS['ISPRO_IMG2PICTURE_ATTR_SRC'] = 'В каких аттрибутах тега img искать ссылку на изображение *<br> (по умолчанию src)';
$MESS['ISPRO_IMG2PICTURE_ATTR_SRC_ERROR'] = 'Поле является обязательным для заполнения (по умолчанию "src")';
$MESS['ISPRO_IMG2PICTURE_CACHE_TTL'] = 'Время хранения кеша (по умолчанию 2592000 - 30 дней)';

$MESS['ISPRO_IMG2PICTURE_BACKGROUNDS'] = 'Обрабатывать изображения в style="background..."';
$MESS['ISPRO_IMG2PICTURE_IMG_COMPRESSION'] = 'Качество сохряняемых изображений (0-100)';
$MESS['ISPRO_IMG2PICTURE_EXCEPTIONS_DIR'] = 'Исключения:<br>каталоги (директории) сайта, где не будет раотать модуль';
$MESS['ISPRO_IMG2PICTURE_EXCEPTIONS_SRC'] = 'Исключения:<br>регулярные выражения по аттрибуту src на изображения, которые не надо преобразовывать';
$MESS['ISPRO_IMG2PICTURE_EXCEPTIONS_TAG'] = 'Исключения:<br>регулярные выражения по тегу img целиком, которые не надо преобразовывать';

$MESS['ISPRO_IMG2PICTURE_MODULE_MODE'] = 'Режим работы модуля';
$MESS['ISPRO_IMG2PICTURE_MODULE_MODE_off'] = 'Выключен';
$MESS['ISPRO_IMG2PICTURE_MODULE_MODE_test'] = 'Тестирование';
$MESS['ISPRO_IMG2PICTURE_MODULE_MODE_on'] = 'Включен';
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
IS_PRO\img2picture\Cimg2picture\doIt(string $content, array $option = [])
</pre>
вернет контент с замененными img на picture<br>
$content - контент в котором необходимо заменить все img<br>
$option - необязательный массив параметров замены
</p>
<hr>
<p>
<pre>
IS_PRO\img2picture\Cimg2picture\MakeWebp(string $src, array $option = [])
</pre>
вернет ссылку на созданный webp<br>
в случае не удачи вернет false<br>
$src - ссылка на изображение<br>
$option - необязательный массив параметров замены
</p>
<hr>
<p>
<pre>
IS_PRO\img2picture\Cimg2picture\GetOptions()
</pre>
вернет параметры модуля
</p>

<h3>Доступные события модуля:</h3>
<p>
Можно перехватить и кастомизировать замену
</p>
<pre>
/* Для перехвата замены img */
AddEventHandler("is_pro.img2picture", "OnPrepareResultImg", "MyPicture");

function MyPicture(&$arResult)
{
	/* Какой-то код меняющий $arResult */
}

/* Для перехвата замены backgropunds */
AddEventHandler("is_pro.img2picture", "OnPrepareResultBackground", "MyBackground");

function MyBackground(&$arResult)
{
	/* Какой-то код меняющий $arResult */
}
</pre>

<h2>Управление модулем на странице</h2>

<p>Иногда надо обновить кеш только на конкретной странице или посмотреть почему что-то неправильно конвертируется</p>
<p>включить отладку на странице ?img2pictureDebug=Y</p>
<p>отчистить кеш и переконвертировать картинки на странице ?img2pictureClearCache=Y</p>


<h2>Поблагодарить</h2>
<p>Можно пройдя по <a href="https://www.sberbank.com/ru/person/dl/jc?linkname=jGTzsJPtWFkAxVW2S" target="_blank">ссылке</a></p>

';
