<?
$arModuleCfg = [
	'MODULE_ID' => 'is_pro.img2picture',
	'options_list' => [
		/* в каком режиме работает модуль off/test/on */
		'MODULE_MODE' => [ 					/* Имя настройки */
			'type' => 'select', 			/* Тип поля настройки */
			'values' => [					/* Значения настройки */
				'off',
				'test',
				'on'
			],
			'default' => 'off'				/* Значение по умолчанию */
		],

		/* Массив адаптивных разрешений и ширины картинок */
		'RESPONSIVE' => [
			'type' => 'json',
			'default' => json_encode([
				[
					"min"=> 0,
					"max"=> 575,
					"width"=> 640
				],
				[
					"min"=> 576,
					"max"=> 767,
					"width"=> 920
				],
				[
					"min"=> 768,
					"max"=> 991,
					"width"=> 1280
				],
				[
					"min"=> 992,
					"max"=> 1199,
					"width"=> 1600
				],
				[
					"min"=> 1200,
					"max"=> 99999,
					"width"=> 1920
				]
			])
		],

		/* Использовать webp Y/N */
		'USE_WEBP' => [
			'type'=>'checkbox',
			'default' => 'Y'
		],

		/* Степень сжатия картинок */
		'IMG_COMPRESSION' => [
			'type' => 'text',
			'default' => '75'
		],

		/* Использовать lazyload Y/N */
		'LAZYLOAD' => [
			'type'=>'checkbox',
			'default' => 'Y'
		],

		/* Обрабатывать картинки в style="background..." Y/N */
		'BACKGROUNDS' => [
			'type'=>'checkbox',
			'default' => 'Y'
		],


		/* в каких аттрибутах тега img искать ссылку на картинку */
		'ATTR_SRC' => [
			'type'=>'textarea',
			'default' => 'src',
		],

		/* Исключения картинок по src*/
		'EXCEPTIONS_SRC' => [
			'type' => 'textarea',
			'default' => implode("\n", ['http.*', '\/\/.*','.*captcha.*', '.*\.php.*', '.*\?.*', '.*\.svg.*', 'data:.*']),
		],

		/* Исключения картинок по тегу*/
		'EXCEPTIONS_TAG' => [
			'type' => 'textarea',
			'default' => ''
		],

		/* Исключения разделов сайта */
		'EXCEPTIONS_DIR' => [
			'type' => 'textarea',
			'default' => implode("\n", ['/bitrix/', '/auth/', '/personal/'])
		],

		/* Время хранения кеша */
		'CACHE_TTL' => [
			'type' => 'text',
			'default' => '2592000' /* 30 дней */
		],
	]
];