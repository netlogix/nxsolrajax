<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solrfluid\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class OptionsFacet extends \ApacheSolrForTypo3\Solrfluid\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet implements \JsonSerializable
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
            'type' => self::TYPE_OPTIONS,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => array_values($this->getOptions()->getArrayCopy()),
            'links' => [
                'reset' => $this->getResetUrl(),
            ]
        ];
    }

}
