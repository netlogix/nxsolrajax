<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\ResetLinkHelperInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class HierarchyFacet extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet implements JsonSerializable
{

    /**
     * @return string
     */
    public function getResetUrl()
    {
        $settings = $this->getConfiguration();
        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], ResetLinkHelperInterface::class, true)) {
            /** @var ResetLinkHelperInterface $linkHelper */
            $linkHelper = GeneralUtility::makeInstance($settings['linkHelper']);
            if ($linkHelper->canHandleResetLink($this)) {
                return $linkHelper->renderResetLink($this);
            }
        }

        $previousRequest = $this->getResultSet()->getUsedSearchRequest();
        return $this->objectManager->get(SearchUriBuilder::class)->getRemoveFacetUri($previousRequest, $this->getName());
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_HIERARCHY,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => array_values($this->getChildNodes()->getArrayCopy()),
            'links' => [
                'reset' => $this->getResetUrl(),
            ]
        ];
    }

}
