<?

namespace IS_PRO\img2picture;

if (class_exists('\IS_PRO\img2picture\Main')) {
	return;
}

class Main
{
	const DIR = '/upload/img2picture/';
	var $image;
	var $image_type;
	var $template;
	var $arParams;

	public function img2picture(&$content)
	{
		global $USER;
		if ($USER->IsAdmin()) {
			return;
		};
		include_once(__DIR__."/install/module.cfg.php");
		$options_list = $arModuleCfg['options_list'];
		foreach ($options_list as $option_name => $option_type) {
			$option[$option_name] = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], $option_name);
			if ($option_type == 'json') {
				$option[$option_name.'_VALUE'] = @json_decode($option[$option_name], true);
			}
		}
		if ($option['MODULE_MODE'] == 'test') {
			if ($_GET['img2picture']) {
				$SESSION['img2picture'] = $_GET['img2picture'];
			}
			$option['MODULE_MODE'] = $SESSION['img2picture'];
		}
		if ($option['MODULE_MODE'] !== 'on') {
			return;
		}
		if (trim($option['EXCEPTIONS_DIR'])) {
			$dirs = explode("\n", $option['EXCEPTIONS_DIR']);
			if (is_array($dirs)) {
				foreach ($dirs as $dir) {
					if (\CSite::InDir($dir)) {
						return;
					};
				};
			};
		};
		include_once(__DIR__.'/classes/main.class.php');
		$option['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		$img2picture = new MainClass($option);
		$img2picture->doIt($content);
	}



}
