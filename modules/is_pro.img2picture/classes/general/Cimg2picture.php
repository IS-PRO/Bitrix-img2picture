<?
namespace IS_PRO\img2picture;

use IS_PRO\img2picture\CImageManupulator;

if (class_exists('\IS_PRO\img2picture\Cimg2picture')) {
	return;
}

class Cimg2picture
{

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

		$json_options = json_encode($option);
		$jsInit = "
		<script>
			const img2picture_options = ".$json_options.";
		</script>
		";
		$jsPath = str_replace($option['DOCUMENT_ROOT'], '', __DIR__). "/../../lib/js/img2picture.min.js";
		$script =  '<script src="'.$jsPath.'"></script>'.$jsInit;
		$content = str_replace('</head>', $script.'</head>', $content);
		$content = self::doIt($content, $option);
	}

	public function doIt(string $content, array $option = [])
	{
		if (count($option) == 0) {
			$option = self::GetOptions();
		}

		$option['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		$img2picture = new CImageManupulator($option);
		$img2picture->doIt($content);
		return $content;
	}

	public function MakeWebp(string $src, array $option = [])
	{
		if (count($option) == 0) {
			$option = self::GetOptions();
		}
		$img2picture = new CImageManupulator($option);
		return $img2picture->ConvertImg2webp($src);
	}

	public function GetOptions() {
		include(__DIR__ . "/../../install/module.cfg.php");
		$options_list = $arModuleCfg['options_list'];
		foreach ($options_list as $option_name => $arOption) {
			$option[$option_name] = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], $option_name);
			if ($arOption['type'] == 'json') {
				$option[$option_name . '_VALUE'] = @json_decode($option[$option_name], true);
			}
		}
		if ($option['MODULE_MODE'] == 'test') {
			if ($_GET['img2picture']) {
				$_SESSION['img2picture'] = $_GET['img2picture'];
			}
			$option['MODULE_MODE'] = $_SESSION['img2picture'];
			$option['DEBUG'] = 'Y';
		};
		$option['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		return $option;
	}

	public function ClearDirCache() {
		$option = self::GetOptions();
		$img2picture = new CImageManupulator($option);
		return $img2picture->ClearDirCache($src);
	}

}
