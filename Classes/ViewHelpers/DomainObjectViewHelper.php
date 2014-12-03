<?php
namespace Netlogix\Nxsolrajax\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sascha Nowak <sascha.nowak@netlogix.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 * @package Leonisolr
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DomainObjectViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper
	 * @return void
	 */
	public function injectPropertyMapper(\TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * Returns a certain DomainObject
	 *
	 * @param int $uid
	 * @param string $targetType
	 *
	 * @return \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
	 */
	public function render($uid, $targetType) {

		try {
			return $this->propertyMapper->convert((string)$uid, $targetType);
		} catch (\TYPO3\CMS\Extbase\Property\Exception $e) {
			return NULL;
		}

	}

}