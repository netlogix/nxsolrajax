<?php
namespace Netlogix\Nxsolrajax\Domain\Model\Dto;

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
class Result implements \Netlogix\Nxcrudextbase\Domain\Model\DataTransfer\DataTransferInterface, \Netlogix\Nxcrudextbase\Domain\Model\DataTransfer\SkipCachingInterface {

	/**
	 * @var array
	 */
	protected $innermostSelf;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $teaser;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @param array $innermostSelf
	 */
	public function __construct($innermostSelf) {
		$this->innermostSelf = $innermostSelf;
	}

	/**
	 * Returns all properties that should be exposed by JsonView
	 * @return array<string>
	 */
	public function getPropertyNamesToBeApiExposed() {
		return array('title', 'teaser', 'content', 'url');
	}

	/**
	 * This object is where this DataTransfer Object is wrapped around. It should *not* be
	 * exposed, because that is the only purpose of this DataTransfer Object object.
	 * @return \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
	 */
	public function getInnermostSelf() {
		$this->innermostSelf;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->innermostSelf['title'];
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTeaser() {
		return $this->innermostSelf['teaser'];
	}

	/**
	 * @param string $teaser
	 */
	public function setTeaser($teaser) {
		$this->teaser = $teaser;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->innermostSelf['content'];
	}

	/**
	 * @param string $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->innermostSelf['url'];
	}

	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

} 