<?
$arModuleCfg = [
	'MODULE_ID' => 'is_pro.img2picture',
	'options_list' => [
		'MODULE_MODE' => 'off', /* в каком режиме работает модуль off/test/on */
		'RESPONSIVE' => 'json', /* Массив адаптивных разрешений и ширины картинок */
		'USE_WEBP' => 'checkbox', /* Использовать webp Y/N */
		'IMG_COMPRESSION' => 'text', /* Степень сжатия картинок */
		'TEMPLATE' => 'textarea', /* Шаблон для замены img тегов */
		'EXCEPTIONS_SRC' => 'textarea', /* Исключения картинок */
		'EXCEPTIONS_DIR' => 'textarea', /* Исключения разделов сайта */
	]
];