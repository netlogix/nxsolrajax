<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DateRangeFacet extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet implements JsonSerializable
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
            'type' => self::TYPE_DATE_RANGE,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => $this->getRange(),
            'links' => [
                'reset' => $this->getResetUrl()
            ]
        ];
    }

}
