<?php
namespace Netlogix\Nxsolrajax\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sascha Nowak <sascha.nowak@netlogix.de>, netlogix GmbH & Co. KG
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TypoScriptFrontendController implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Send cache header for uncached solr results
	 */
	public function sendCacheHeaders() {

		/** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $typoScriptFrontendController */
		$typoScriptFrontendController = $GLOBALS['TSFE'];
		if (!empty($typoScriptFrontendController->config['config']['enforceSendCacheHeaders']) && !$typoScriptFrontendController->beUserLogin && !$typoScriptFrontendController->doWorkspacePreview()) {
			$headers = array(
				'Last-Modified: ' . gmdate('D, d M Y H:i:s T', $typoScriptFrontendController->register['SYS_LASTCHANGED']),
				'Expires: ' . gmdate('D, d M Y H:i:s T', $typoScriptFrontendController->cacheExpires),
				'ETag: "' . md5($typoScriptFrontendController->content) . '"',
				'Cache-Control: max-age=' . ($typoScriptFrontendController->cacheExpires - $GLOBALS['EXEC_TIME']),
				// no-cache
				'Pragma: public'
			);
			// Send headers:
			foreach ($headers as $header) {
				header($header);
			}
		}

	}

}