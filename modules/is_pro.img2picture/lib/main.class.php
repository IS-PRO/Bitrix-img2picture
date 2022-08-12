<?

namespace IS_PRO\img2picture;

if (class_exists('\IS_PRO\img2picture\MainClass')) {
	return;
}


class MainClass extends CSimpleImage
{
	const DIR = '/upload/img2picture/';
	const max_width = 99999;
	const cachePath  = 'img2picture';
	var $arParams = array();
	var $doc_root;

	public function __construct($arParams)
	{
		if (empty($arParams['DOCUMENT_ROOT'])) {
			$arParams['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
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
		} else {
			unset($arParams['RESPONSIVE_VALUE']);
		};
		if ((int) $arParams['CACHE_TTL'] == 0) {
			$arParams['CACHE_TTL'] = 2592000; /* 30 дней */
		};


		$this->arParams = $arParams;
	}


	function doIt(&$content)
	{
		$arParams = $this->arParams;
		$arPicture = $this->get_tags('picture', $content, true);
		$arImg = $this->get_tags('img', $content, false);

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cachePath = self::cachePath;
		$cacheTtl = (int) $arParams['CACHE_TTL'];


		foreach ($arImg as $img) {

			$need = true;
			$arResult = [];

			if (trim($img['src']) == '') {
				$need = false;
				continue;
			}


			$cacheKey =  md5($img['tag']);;

			if ($cache->initCache($cacheTtl, $cacheKey, $cachePath)) {
				$place = $cache->getVars();
			} elseif ($cache->startDataCache()) {

				$place = '';
				/* проверим на исключения */
				if (is_array($arParams['EXCEPTIONS'])) {
					foreach ($arParams['EXCEPTIONS'] as $exception) {
						if (preg_match($exception, $img['src'])) {
							$need = false;
							break;
						};
					};
				};
				if ($need) {
					/* Проверим есть наше изображение уже в picture */
					if (is_array($arPicture)) {
						foreach ($arPicture as $picture) {
							if (strpos($picture['tag'], $img['tag'])) {
								$need = false;
								break;
							};
						};
					};
				};
				if ($need) {
					$arResult['img'] = $img;
					$arResult['sources'] = [];
					if (is_array($arParams['WIDTH'])) {
						$files = $this->ResponsiveFiles($img['src'], $arParams['WIDTH']);
						if ($files) {
							$arResult['FILES'] =  $files;

							foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
								if (!is_array($arResult['FILES'][$val['width']])) {
									continue;
								}
								$addsourse = [];
								foreach ($arResult['FILES'][$val['width']] as $file_type => $file_src) {
									if ($file_type == 'webp') {
										$type = 'type="image/webp"';
										$index = 0;
									} else {
										$ext = substr(strrchr($file_src, '.'), 1);
										if ($ext == 'jpg') {
											$ext = 'jpeg';
										}
										$type = 'type="image/' . $ext . '"';
										$index = 1;
									}
									$media = 'media="';
									$mediaand = '';
									if ((int) $val['min'] > 0) {
										$media .= $mediaand . '(min-width: ' . $val['min'] . 'px)';
										$mediaand = ' and ';
									}
									if ((int) $val['max'] > (int) $val['min']) {
										$media .= $mediaand . '(max-width: ' . $val['max'] . 'px)';
									}
									$media .= '"';
									$addsourse[$index] = '<source srcset="' . $file_src . '" ' . $media . ' ' . $type . '>';
								}
								ksort($addsourse);
								$arResult['sources'] = array_merge($arResult['sources'], $addsourse);
							};
						};
					};

					$arResult['FILES']['original']['src'] = $img['src'];
					$arResult['FILES']['original']['type'] = 'image/' . substr(strrchr($img['src'], '.'), 1);

					if ($this->arParams['USE_WEBP'] == 'Y') {
						$arResult['FILES']['original']['webp'] = $this->ConvertImg2webp($img['src']);
						if ($arResult['FILES']['original']['webp']) {
							$arResult['sources'][] = '<source srcset="' . $arResult['FILES']['original']['webp'] . '"  type="image/webp">';
						};
					};
					$place = '';
					ob_start();
					@eval('?>' . $this->arParams['TEMPLATE'] . '<?');
					$place = ob_get_contents();
					ob_end_clean();
				}
				$cache->endDataCache($place);
			}

			if (trim($place) != '') {
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
			$newsrc = str_replace('//', '/', $newsrc);
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
					$resized = $this->smallTo($width, $height);
					if ($resized) {
						$this->CreateDir($filename, true);
						if (!$this->save($filename, $this->image_type, $this->arParams['IMG_COMPRESSION'])) {
							unset($arResult[$width]['src']);
						};
					} else {
						unset($arResult[$width]['src']);
					}
				} else {
					unset($arResult[$width]['src']);
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
						$resized = $this->smallTo($width, $height);
					}
					if ($resized) {
						$this->CreateDir($filename, true);
						if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {
							unset($arResult[$width]['webp']);
						};
						if (filesize($filename) == 0) {
							unset($arResult[$width]['webp']);
						}
					} else {
						unset($arResult[$width]['webp']);
					}
				} else {
					unset($arResult[$width]['webp']);
				}
			} else {
				if (filesize($filename) == 0) {
					unset($arResult[$width]['webp']);
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
		$webp = str_replace('//', '/', $webp);

		if (!file_exists($doc_root . $webp)) {
			$need = true;
		} else {
			if (filesize($filename) == 0) {
				return false;
			}
		}

		if ($need) {
			$filename = $doc_root . $webp;
			$this->CreateDir($filename, true);
			if (!$this->load($doc_root . $src)) {
				return false;
			};
			if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {
				return false;
			};
			if (filesize($filename) == 0) {
				return false;
			}
		}

		return $webp;
	}

	public function ClearDirCache() {
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		self::RemoveDir($doc_root. self::DIR);
		self::RemoveDir($doc_root. '/bitrix/cache/'.self::cachePath);

	}


	public function CreateDir($path,  $lastIsFile = false)
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

	public function RemoveDir($path)
	{

		$files = glob($path . '/*');
		if ($files) {
			foreach ($files as $file) {
				is_dir($file) ? self::RemoveDir($file) : @unlink($file);
			}
		}
		@rmdir($path);

		return;
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
}
