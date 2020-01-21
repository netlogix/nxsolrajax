<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping;

use JsonSerializable;

class GroupItem extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItem implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'label' => $this->getGroupValue(),
            'name' => $this->getGroupValue(),
            'totalResults' => $this->getAllResultCount(),
            'start' => $this->getStart(),
            'maxScore' => $this->getMaximumScore(),
            'items' => $this->getSearchResults()->getArrayCopy(),
        ];
    }
}
