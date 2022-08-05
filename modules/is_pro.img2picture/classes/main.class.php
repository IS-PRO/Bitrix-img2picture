<?

namespace IS_PRO\img2picture;

if (class_exists('\IS_PRO\img2picture\MainClass')) {
	return;
}

class MainClass
{
	const DIR = '/upload/img2picture/';
	const max_width = 99999;
	var $image;
	var $image_type;
	var $arParams = array();
	var $doc_root;

	public function __construct($arParams)
	{
		if (empty($arParams['DOCUMENT_ROOT'])) {
			$arParams['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'];
		};
		if (!empty($arParams['EXCEPTIONS_SRC'])) {
			$arExceptionsSrc = explode("\n", $arParams['EXCEPTIONS_SRC']);
			if (is_array($arExceptionsSrc)) {
				foreach ($arExceptionsSrc as $k => $v) {
					$arExceptionsSrc[$k] = '|' . trim($v) . '|';
				};
				$arParams['EXCEPTIONS'] = $arExceptionsSrc;
			};
		};
		if (!is_numeric($arParams['IMG_COMPRESSION'])) {
			$arParams['IMG_COMPRESSION'] = 75;
		};
		if (is_array($arParams['RESPONSIVE_VALUE'])) {
			foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
				$arParams['WIDTH'][] = $val['width'];
			};
			rsort($arParams['WIDTH']);
		};




		$this->arParams = $arParams;
	}


	function doIt(&$content)
	{
		$arParams = $this->arParams;
		$arPicture = $this->get_tags('picture', $content, true);
		$arImg = $this->get_tags('img', $content, false);
		foreach ($arImg as $img) {

			$need = true;
			$arResult = [];

			if (trim($img['src']) == '') {
				$need = false;
				continue;
			} 
			

			/* проверим на исключения */
			
			if (is_array($arParams['EXCEPTIONS'])) {
				foreach ($arParams['EXCEPTIONS'] as $exception) {
					if (preg_match($exception, $img['src'])) {
						$need = false;
						break;
					};
				};
			};
			if (!$need) {
				continue;
			};

			/* Проверим есть наше изображение уже в picture */
			if (is_array($arPicture)) {
				foreach ($arPicture as $picture) {
					if (strpos($picture['tag'], $img['tag'])) {
						$need = false;
						break;
					};
				};
			};
			if (!$need) {
				continue;
			};

			$arResult['img'] = $img;
			if (is_array($arParams['WIDTH'])) {
				$files = $this->ResponsiveFiles($img['src'], $arParams['WIDTH']);
				if (!$files) {
					continue;
				};
				$arResult['FILES'] =  $files;

				if (is_array($arParams['RESPONSIVE_VALUE'])) {
					foreach ($arParams['RESPONSIVE_VALUE'] as $key=>$val) {
						if (!is_array($arResult['FILES'][$val['width']])) {
							continue;
						}
						foreach ($arResult['FILES'][$val['width']] as $file_type => $file_src) {
							if ($file_type == 'webp') {
								$type = 'type="image/webp"';
							} else {
								$type = '';
							}
							$arResult['sources'][] = '<source srcset="'.$file_src.'" media="(min-width: '.$val['min'].'px) and (max-width: '.$val['max'].'px)" '.$type.'>';
						}
					}
				}
			};

			$arResult['FILES']['original']['src'] = $img['src'];
			$arResult['sources'][] = '<source srcset="'.$arResult['FILES']['original']['src'].'">';

			if ($this->arParams['USE_WEBP'] == 'Y') {
				$arResult['FILES']['original']['webp'] = $this->ConvertImg2webp($img['src']);
				if ($arResult['FILES']['original']['webp']) {
					$arResult['sources'][] = '<source srcset="'.$arResult['FILES']['original']['webp'].'"  type="image/webp">';
				}
			};
			$place = '';
			ob_start();
			@eval ('?>'.$this->arParams['TEMPLATE'].'<?');
			$place = ob_get_contents();
			ob_end_clean();
			if (trim($place) != '') {
				//$content .= $img['tag'];
				$content = str_replace($img['tag'], $place, $content);
			}
		}
	}

	function ResponsiveFiles(string $src, array $arWidth)
	{
		$doc_root = $this->arParams['DOCUMENT_ROOT'];

		$loaded = false;
		$height = self::max_width;

		/* проверим существует ли файл вообще */
		if (!file_exists($doc_root . $src)) {
			return false;
		};

		$arResult = [];

		/* подготовим файлы для каждой ширины */
		foreach ($arWidth as $width) {
			$resized = false;
			$newsrc = self::DIR . '/' . $width . '/' .  $src;
			$filename = $doc_root . $newsrc;

			$arResult[$width]['src'] = $newsrc;

			if (!file_exists($filename)) {
				if (!$loaded) {
					if (!$this->load($doc_root . $src)) {
						return false;
					};
					$loaded = true;
				};
				if ($loaded) {
					$this->smallTo($width, $height);
					$resized = true;
					$this->CreateDir($filename, true);
					if (!$this->save($filename, $this->image_type, $this->arParams['IMG_COMPRESSION'])) {
						$arResult[$width]['src'] = '';
					};
				}
			}
			if ($this->arParams['USE_WEBP'] !== 'Y') {
				continue;
			};

			/* подготовим webp */
			$filename = $doc_root . $newsrc . '.webp';
			$arResult[$width]['webp'] = $newsrc . '.webp';
			if (!file_exists($filename)) {
				if (!$loaded) {
					if (!$this->load($doc_root . $src)) {
						return false;
					};
					$loaded = true;
				};
				if ($loaded) {
					if (!$resized) {
						$this->smallTo($width, $height);
					}
					$this->CreateDir($filename, true);
					if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {
						$arResult[$width]['webp'] = '';
					};
				}
			}
		}
		return $arResult;
	}

	function ConvertImg2webp($src)
	{
		if ($this->arParams['USE_WEBP'] !== 'Y') {
			return false;
		};
		$need = false;
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		$webp = self::DIR . $src . '.webp';

		if (!file_exists($doc_root . $webp)) {
			$need = true;
		}

		if ($need) {
			$this->CreateDir($doc_root . $webp, true);
			if (!$this->load($doc_root . $src)) {
				return false;
			};
			if (!$this->save($doc_root . $webp, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {
				return false;
			};
		}

		return $webp;
	}

	function CreateDir($path,  $lastIsFile = false)
	{
		$dirs = explode('/', $path);
		if ($lastIsFile) {
			unset($dirs[count($dirs) - 1]);
		}
		$resultdir = '';
		foreach ($dirs as $dir) {
			$resultdir .= $dir;
			if ($dir != '') {
				@mkdir($resultdir);
			};
			$resultdir .= '/';
		}
	}

	function get_tags($tag, $content, $haveClosedTag = true)
	{
		if ($haveClosedTag) {
			$arTag['tag'] = '/(<' . $tag . '[^>]*>)(.*)<\/' . $tag . '>/ismuU';;
		} else {
			$arTag['tag'] = '/(<' . $tag . '[^>]*>)/ismuU';
		};
		$arTag['attr'][0] = '/\s+([a-zA-Z-]+)\s*=\s*"([^"]*)"/ismuU';
		$arTag['attr'][] = str_replace('"', "'", $arTag['attr'][0]);
		$result = array();
		if (preg_match_all($arTag['tag'], $content, $matches)) {
			foreach ($matches[0] as $k => $match) {
				$res_tag = array();
				$res_tag['tag'] = $match;
				if (isset($matches[1][$k])) {
					foreach ($arTag['attr'] as $arTagAttr) {
						unset($attr_matches);
						preg_match_all($arTagAttr, $matches[1][$k], $attr_matches);
						if (is_array($attr_matches[1])) {
							foreach ($attr_matches[1] as $key => $val) {
								$res_tag[$val] = $attr_matches[2][$key];
							};
						};
					};
				};
				if (isset($matches[2][$k])) {
					$res_tag['text'] = $matches[2][$k];
				};
				$result[] = $res_tag;
			};
		};
		return $result;
	}

	/**
	 *   Load image (Загрузит картинку)
	 *   @param  $filename - имя файла
	 *   @return bool - true is ok
	 */
	function load($filename)
	{
		$image_info = getimagesize($filename);
		$this->image_type = $image_info[2];
		if ($this->image_type == IMAGETYPE_JPEG) {
			$this->image = imagecreatefromjpeg($filename);
		} elseif ($this->image_type == IMAGETYPE_GIF) {
			$this->image = imagecreatefromgif($filename);
		} elseif ($this->image_type == IMAGETYPE_PNG) {
			$this->image = imagecreatefrompng($filename);
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
		} elseif ($this->image_type == IMAGETYPE_WEBP) {
			$this->image = imagecreatefromwebp($filename);
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
		} else {
			$this->image_type = false;
			return false;
		}
		return true;
	}

	/**
	 * Save Image (Сохранит изображение)
	 *   @param $filename - filename (имя файла)
	 *   @param $image_type - type (тип файла) (IMAGETYPE_JPEG / IMAGETYPE_GIF / IMAGETYPE_PNG / IMAGETYPE_WEBP)
	 *   @param $compression - compression Jpeg/Webp (компрессия для Jpeg и Webp)
	 *   @param $permissions - permissions (Права доступа к файлу)
	 */
	function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null)
	{
		$result = false;
		if ($image_type == IMAGETYPE_JPEG) {
			$result = imagejpeg($this->image, $filename, $compression);
		} elseif ($image_type == IMAGETYPE_GIF) {
			$result = imagegif($this->image, $filename);
		} elseif ($image_type == IMAGETYPE_PNG) {
			$result = imagepng($this->image, $filename);
		} elseif ($image_type == IMAGETYPE_WEBP) {
			$result = imagewebp($this->image, $filename, $compression);
		};
		if ($permissions != null) {
			chmod($filename, $permissions);
		}
		return $result;
	}

	/**
	 * getWidth() - Вернет ширину загруженого изображения
	 * @return width of loaded image
	 */
	function getWidth()
	{
		return imagesx($this->image);
	}

	/**
	 * getHeight() - Вернет высоту загруженого изображения
	 * @return height of loaded image
	 */
	function getHeight()
	{
		return imagesy($this->image);
	}

	/**
	 * Resize/Scale loaded image to height
	 * Масщтабирует изображение до определенной высоты
	 * @param $height - px (высота)
	 */
	function resizeToHeight($height)
	{
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width, $height);
	}

	/**
	 * Resize/Scale loaded image to width
	 * Масштабирует изображение до определенной ширины
	 * @param $width - px (Ширина)
	 */
	function resizeToWidth($width)
	{
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width, $height);
	}

	/**
	 * Percent Scaling
	 * Масштабирует по процентному соотношению
	 * @param $scale - percent (процент)
	 */
	function scale($scale)
	{
		$width = $this->getWidth() * $scale / 100;
		$height = $this->getheight() * $scale / 100;
		$this->resize($width, $height);
	}

	/**
	 * Resize/Scale loaded image
	 * Масштабирует изображение
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	function resize($width, $height)
	{
		$new_image = imagecreatetruecolor($width, $height);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}

	/**
	 * Resize/Scale loaded image to cover area
	 * Масштабирует изображение чтобы заполнить область
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	function cover($width, $height)
	{
		$w = $this->getWidth();
		if ($width != $w) {
			$this->resizeToWidth($width);
		}
		$h = $this->getHeight();
		if ($height > $h) {
			$this->resizeToHeight($height);
		}
		$this->wrapInTo($width, $height);
	}

	/**
	 * Wrap loaded image to area
	 * Обрезает все что не вмещается в область
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	function wrapInTo($width, $height)
	{
		$new_image = imagecreatetruecolor($width, $height);
		$w = $this->getWidth();
		$h = $this->getHeight();
		if ($width > $w) {
			$dst_x = round(($width - $w) / 2);
			$src_x = 0;
			$dst_w = $w;
			$src_w = $w;
		} else {
			$dst_x = 0;
			$src_x = round(($w - $width) / 2);
			$dst_w = $width;
			$src_w = $width;
		}
		if ($height > $h) {
			$dst_y = round(($height - $h) / 2);
			$src_y = 0;
			$dst_h = $h;
			$src_h = $h;
		} else {
			$dst_y = 0;
			$src_y = round(($h - $height) / 2);
			$dst_h = $height;
			$src_h = $height;
		}
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		$transparentindex = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
		imagefill($new_image, 0, 0, $transparentindex);
		imagecopyresampled($new_image, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		$this->image = $new_image;
	}

	/**
	 * Resize/Scale loaded image in to area
	 * Масштабюировать чтобы изображение влезло в рамки
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	function resizeInTo($width, $height)
	{
		$ratiow = $width / $this->getWidth() * 100;
		$ratioh = $height / $this->getHeight() * 100;
		$ratio = min($ratiow, $ratioh);
		$this->scale($ratio);
	}


	/**
	 * Resize/Scale loaded image in to area if this bigger
	 * Уменьшает изображение если текущее больше
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	function smallTo($width, $height)
	{
		/* */
		if (($this->getWidth() > $width) or ($this->getHeight() > $height)) {
			$this->resizeInTo($width, $height);
		};
	}

	/**
	 * Crop loaded image by coordinates
	 * Вырезать кусок по координатам углов
	 * @param $x1, $y1, $x2, $y2 - coordinates (координаты углов)
	 */
	function crop($x1, $y1, $x2, $y2)
	{
		$w = abs($x2 - $x1);
		$h = abs($y2 - $y1);
		$x = min($x1, $x2);
		$y = min($y1, $y2);
		$new_image = imagecreatetruecolor($w, $h);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		imagecopy($new_image, $this->image, 0, 0, $x, $y, $w, $h);
		$this->image = $new_image;
	}
}
