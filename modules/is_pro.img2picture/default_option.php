<?
$is_pro_img2picture_default_options = [
	'RESPONSIVE' => json_encode([
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
	]),
	'USE_WEBP' => 'Y',
	'IMG_COMPRESSION' => '75',
	'TEMPLATE' => file_get_contents(__DIR__.'/default_template.php'),
	'EXCEPTIONS_DIR' => implode("\n", ['/bitrix/', '/auth/', '/personal/']),
	'EXCEPTIONS_SRC' => implode("\n", ['http.*', '.*captcha.*', '.*\.php.*', '.*\?.*', '.*\.svg.*', 'data:.*']),
];
?>