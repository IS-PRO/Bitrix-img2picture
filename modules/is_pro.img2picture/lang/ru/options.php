<?
$MESS['ISPRO_IMG2PICTURE_TAB_SET_OPTION'] = 'Настройки Img2Picture';
$MESS['ISPRO_IMG2PICTURE_TAB_TITLE_OPTION'] = 'Настрйоки';
$MESS['ISPRO_ING2PICTURE_RESPONSIVE'] = 'Адаптив';
$MESS['ISPRO_ING2PICTURE_MIN_SCREEN_WIDTH'] = 'Минимальная ширина экрана экрана (min-width), px';
$MESS['ISPRO_ING2PICTURE_MAX_SCREEN_WIDTH'] = 'Максимальная ширина экрана экрана (max-width), px';
$MESS['ISPRO_ING2PICTURE_MAX_IMG_WIDTH'] = 'Ширина изображения px';
$MESS['ISPRO_ING2PICTURE_TEMPLATE'] = 'Шаблон замены тега img';
$MESS['ISPRO_ING2PICTURE_USE_WEBP'] = 'Применить конвертацию изображений в webp';
$MESS['ISPRO_ING2PICTURE_IMG_COMPRESSION'] = 'Качество сохряняемых изображений (0-100)';
$MESS['ISPRO_ING2PICTURE_EXCEPTIONS_DIR'] = 'Исключения: каталоги (директории) сайта, где не будет раотать модуль';
$MESS['ISPRO_ING2PICTURE_EXCEPTIONS_SRC'] = 'Исключения: регулярные выражения для ссылок на изображения, которые не надо преобразовывать';
$MESS['ISPRO_ING2PICTURE_MODULE_MODE'] = 'Режим работы модуля';
$MESS['ISPRO_ING2PICTURE_MODULE_MODE_off'] = 'Выключен';
$MESS['ISPRO_ING2PICTURE_MODULE_MODE_test'] = 'Тестирование';
$MESS['ISPRO_ING2PICTURE_MODULE_MODE_on'] = 'Включен';


$MESS['ISPRO_IMG2PICTURE_INFO'] = '
Модуль заменяет теги img на теги picture с ресайзом и конвертацией изображений.
<br>
Модуль не работает если текущий пользователь адмиристратор - для того чтобы не заменять теги во время редактирования контента.
<br>
Т.е. тестирование работы модуля надо проводить не авторизованным пользователем.
<br>
<br>
В режиме "Тестирование":<br>
включить модуль дописав get параметр ?img2picture=on<br>
выключить модуль дописав get параметр ?img2picture=off<br>
';