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
        $previousRequest = $this->getResultSet()->getUsedSearchRequest();
        return GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class)->getRemoveFacetUri($previousRequest, $this->getName());
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
