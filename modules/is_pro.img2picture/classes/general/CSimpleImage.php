<?php

namespace IS_PRO\img2picture;

if (class_exists('\IS_PRO\img2picture\CSimpleImage')) {
	return;
}

class CSimpleImage
{

	var $image;
	var $image_type = false;

	/**
	 *   Load image (Загрузит картинку)
	 *   @param  $filename - имя файла
	 *   @return bool - true is ok
	 */
	public function load($filename)
	{
		$image_info = getimagesize($filename);
		$this->image_type = $image_info[2];
		if ($this->image_type == IMAGETYPE_JPEG) {
			$this->image = imagecreatefromjpeg($filename);
		} elseif ($this->image_type == IMAGETYPE_GIF) {
			$this->image = imagecreatefromgif($filename);
		} elseif ($this->image_type == IMAGETYPE_PNG) {
			$this->image = imagecreatefrompng($filename);
		} elseif ($this->image_type == IMAGETYPE_WEBP) {
			$this->image = imagecreatefromwebp($filename);
		} else {
			$this->image_type = false;
			return false;
		}
		imagepalettetotruecolor($this->image);
		imagealphablending($this->image, false);
		imagesavealpha($this->image, true);
		return true;
	}

	/**
	 * Get Image (получить экземпляр изображения)
	 */
	public function GetImage()
	{
		if (!$this->image_type) {
			return false;
		};
		return $this->image;
	}

	/**
	 * Save Image (Сохранит изображение)
	 *   @param $filename - filename (имя файла)
	 *   @param $image_type - type (тип файла) (IMAGETYPE_JPEG / IMAGETYPE_GIF / IMAGETYPE_PNG / IMAGETYPE_WEBP)
	 *   @param $compression - compression Jpeg/Webp (компрессия для Jpeg и Webp)
	 *   @param $permissions - permissions (Права доступа к файлу)
	 */
	public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null)
	{
		if ($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image, $filename, $compression);
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif($this->image, $filename);
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng($this->image, $filename);
		} elseif ($image_type == IMAGETYPE_WEBP) {
			imagewebp($this->image, $filename, $compression);
		} else {
			return false;
		};
		if (file_exists($filename)) {
			if ($permissions != null) {
				chmod($filename, $permissions);
			}
			if (filesize($filename) > 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Output image (Вывод изображения в браузер)
	 * @param $image_type - type image (Тип изображения) (IMAGETYPE_JPEG / IMAGETYPE_GIF / IMAGETYPE_PNG / IMAGETYPE_WEBP)
	 */
	public function output($image_type = IMAGETYPE_JPEG)
	{
		if ($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image);
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif($this->image);
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng($this->image);
		} elseif ($image_type == IMAGETYPE_WEBP) {
			imagewebp($this->image);
		} else {
			return false;
		}
		return true;
	}

	/**
	 * getWidth() - Вернет ширину загруженого изображения
	 * @return width of loaded image
	 */
	public function getWidth()
	{
		if (!$this->image_type) {
			return false;
		};
		return imagesx($this->image);
	}

	/**
	 * getHeight() - Вернет высоту загруженого изображения
	 * @return height of loaded image
	 */
	public function getHeight()
	{
		if (!$this->image_type) {
			return false;
		};
		return imagesy($this->image);
	}

	/**
	 * Resize/Scale loaded image to height
	 * Масщтабирует изображение до определенной высоты
	 * @param $height - px (высота)
	 */
	public function resizeToHeight($height)
	{
		if (!$this->image_type) {
			return false;
		};
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		return $this->resize($width, $height);
	}

	/**
	 * Resize/Scale loaded image to width
	 * Масштабирует изображение до определенной ширины
	 * @param $width - px (Ширина)
	 */
	public function resizeToWidth($width)
	{
		if (!$this->image_type) {
			return false;
		};
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		return $this->resize($width, $height);
	}

	/**
	 * Percent Scaling
	 * Масштабирует по процентному соотношению
	 * @param $scale - percent (процент)
	 */
	public function scale($scale)
	{
		if (!$this->image_type) {
			return false;
		};
		$width = $this->getWidth() * $scale / 100;
		$height = $this->getheight() * $scale / 100;
		return $this->resize($width, $height);
	}

	/**
	 * Resize/Scale loaded image
	 * Масштабирует изображение
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	public function resize($width, $height)
	{
		if (!$this->image_type) {
			return false;
		};
		$new_image = imagecreatetruecolor($width, $height);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
		return  true;
	}

	/**
	 * Resize/Scale loaded image to cover area
	 * Масштабирует изображение чтобы заполнить область
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	public function cover($width, $height)
	{
		if (!$this->image_type) {
			return false;
		};
		$w = $this->getWidth();
		if ($width != $w) {
			$this->resizeToWidth($width);
		}
		$h = $this->getHeight();
		if ($height > $h) {
			$this->resizeToHeight($height);
		}
		return $this->wrapInTo($width, $height);
	}

	/**
	 * Wrap loaded image to area
	 * Обрезает все что не вмещается в область
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	public function wrapInTo($width, $height)
	{
		if (!$this->image_type) {
			return false;
		};
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
		return true;
	}

	/**
	 * Resize/Scale loaded image in to area
	 * Масштабюировать чтобы изображение влезло в рамки
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	public function resizeInTo($width, $height)
	{
		if (!$this->image_type) {
			return false;
		};
		$ratiow = $width / $this->getWidth() * 100;
		$ratioh = $height / $this->getHeight() * 100;
		$ratio = min($ratiow, $ratioh);
		return $this->scale($ratio);
	}


	/**
	 * Resize/Scale loaded image in to area if this bigger
	 * Уменьшает изображение если текущее больше
	 * @param $width - px (Ширина)
	 * @param $height - px (высота)
	 */
	public function smallTo($width, $height)
	{
		/* */
		if (!$this->image_type) {
			return false;
		};
		if (($this->getWidth() > $width) or ($this->getHeight() > $height)) {
			return $this->resizeInTo($width, $height);
		} else {
			return false;
		};
	}

	/**
	 * Crop loaded image by coordinates
	 * Вырезать кусок по координатам углов
	 * @param $x1, $y1, $x2, $y2 - coordinates (координаты углов)
	 */
	public function crop($x1, $y1, $x2, $y2)
	{
		if (!$this->image_type) {
			return false;
		};
		$w = abs($x2 - $x1);
		$h = abs($y2 - $y1);
		$x = min($x1, $x2);
		$y = min($y1, $y2);
		$new_image = imagecreatetruecolor($w, $h);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		imagecopy($new_image, $this->image, 0, 0, $x, $y, $w, $h);
		$this->image = $new_image;
		return true;
	}
}
