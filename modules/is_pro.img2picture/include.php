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
		if (\CSite::InDir('/bitrix/')) {
			return;
		};
		$option = self::GetOptions();
		if ($option['MODULE_MODE'] == 'test') {
			if ($_GET['img2picture']) {
				$_SESSION['img2picture'] = $_GET['img2picture'];
			}
			$option['MODULE_MODE'] = $_SESSION['img2picture'];
			$option['DEBUG'] = 'Y';
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
<<<<<<< HEAD
		include_once(__DIR__ . '/lib/main.class.php');
=======
		$content = self::doIt($content, $option);
	}

	public function doIt(string $content, array $option = [])
	{
		if (count($option) == 0) {
			$option = self::GetOptions();
		}
		include_once(__DIR__ . '/classes/main.class.php');
>>>>>>> 7ae4de4d5745d0de621ee8e155050d04a6303291
		$option['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		$img2picture = new MainClass($option);
		$img2picture->doIt($content);
		return $content;
	}

	public function MakeWebp(string $src, array $option = [])
	{
		if (count($option) == 0) {
			$option = self::GetOptions();
		}
		include_once(__DIR__ . '/classes/main.class.php');
		$option['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		$img2picture = new MainClass($option);
		return $img2picture->ConvertImg2webp($src);
	}

	public function GetOptions() {
		include_once(__DIR__ . "/install/module.cfg.php");
		$options_list = $arModuleCfg['options_list'];
		foreach ($options_list as $option_name => $option_type) {
			$option[$option_name] = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], $option_name);
			if ($option_type == 'json') {
				$option[$option_name . '_VALUE'] = @json_decode($option[$option_name], true);
			}
		}
		return $option;
	}
}
