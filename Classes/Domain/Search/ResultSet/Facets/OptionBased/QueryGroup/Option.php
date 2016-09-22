<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup;

use ApacheSolrForTypo3\Solrfluid\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Option extends \ApacheSolrForTypo3\Solrfluid\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option implements \JsonSerializable
{

    /**
     * @return string
     */
    public function getUrl()
    {
        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $searchUriBuilder = $objectManager->get(SearchUriBuilder::class);

        return $searchUriBuilder->getSetFacetValueUri(
            $previousRequest,
            $this->getFacet()->getName(),
            $this->getUriValue()
        );
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'label' => $this->getLabel(),
            'count' => $this->getDocumentCount(),
            'selected' => $this->getSelected(),
            'links' => [
                'self' => $this->getUrl(),
            ]
        ];
    }
}