<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class HierarchyFacet extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet  implements \JsonSerializable
{

    /**
     * @return string
     */
    public function getResetUrl()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $searchUriBuilder = $objectManager->get(SearchUriBuilder::class);
        $previousRequest = $this->getResultSet()->getUsedSearchRequest();
        return $searchUriBuilder->getRemoveFacetUri($previousRequest, $this->getName());
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
