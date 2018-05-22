<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DateRange extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange implements \JsonSerializable
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
            '{dateRange}'
        );
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'selected' => $this->getLabel() !== '',
            'start' => $this->getStartRequested() ? $this->getStartRequested()->getTimestamp() : '',
            'end' => $this->getEndRequested() ? $this->getEndRequested()->getTimestamp() : '',
            'min' => $this->getStartInResponse()->getTimestamp(),
            'max' => $this->getStartInResponse()->getTimestamp(),
            'links' => [
                'self' => $this->getUrl(),
            ]
        ];
    }

}
