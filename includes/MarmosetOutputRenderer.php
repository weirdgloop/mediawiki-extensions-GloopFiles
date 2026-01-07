<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extensions\GloopFiles;

use MediaTransformOutput;
use MediaWiki\MediaWiki\Html;

class MarmosetOutputRenderer extends MediaTransformOutput {

	/**
	 * @var string
	 */
	private $sourceFileURL;

	/**
	 * @var string
	 */
	private $fileName;

	/**
	 * @var array
	 */
	private $params;

	/**
	 * @param \File $file
	 * @param array $params
	 * @return void
	 */
	public function __construct( $file, $params ) {
		global $wgGloopFilesConfig;
		$this->file = $file;
		$this->params = $params;
		$this->sourceFileURL = $file->getFullUrl();
		$this->fileName = $file->getTitle();
		$this->width = (isset($params['width']) ? $params['width'] : null);
		$this->height = (isset($params['height']) ? $params['height'] : null);
		$this->autostart = (isset($params['autostart']) ? $params['autostart'] : false);
		$this->background = (isset($params['background']) ? $params['background'] : false);
		$this->userinterface = (isset($params['userinterface']) ? $params['userinterface'] : false);

		if (!$this->width) {
			if ( (int) $wgGloopFilesConfig['width'] AND (int) $wgGloopFilesConfig['width'] > 0 ) {
	            $this->width = (int) $wgGloopFilesConfig['width'];
	        } else {
	        	$this->width = 500;
	        }
		}
		if (!$this->height) {
			if ( (int) $wgGloopFilesConfig['height'] AND (int) $wgGloopFilesConfig['height'] > 0 ) {
	            $this->height = (int) $wgGloopFilesConfig['height'];
	        } else {
	        	$this->height = 500;
	        }
		}

		// Hacky correction for packed style galleries
		// See getThumbParams() in PackedImageGallery.php
		$test_width = ((($this->height / 1.5) * 10) + 100) * 1.5;
		if ($this->width == $test_width) {
			$this->width = $this->height;
		}
	}

	/**
	 * @param array $options
	 *
	 * @return string
	 */
	public function toHtml( $options = [] ) {
		$params = $this->params;

		$style = [];
		$style[] = "width: {$this->getWidth()}px;";
		$style[] = "height: {$this->getHeight()}px;";
		if (!empty($options['valign'])) {
			$style[] = "vertical-align: {$options['valign']};";
		}

		$class = ['gf-mview-frame'];
		if (!empty($options['img-class'])) {
			$class[] = $options['img-class'];
		}
		
		$Output = wfMessage('gloopfiles-mview-alt',$this->fileName)->parse();
		return Html::rawElement('div', [
			'src' => $this->sourceFileURL,
			'width' => $this->getWidth(),
			'height' => $this->getHeight(),
			'style' => implode(' ', $style),
			'class' => implode(' ', $class),
			'data-autostart' => $this->autostart,
			'data-background' => $this->background,
			'data-userinterface' => $this->userinterface
		], $Output);
	}
}
