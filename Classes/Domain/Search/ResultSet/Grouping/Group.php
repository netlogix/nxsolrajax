<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping;

use JsonSerializable;

class Group extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [
            'groupName' => $this->getGroupName(),
            'resultsPerPage' => $this->getResultsPerPage(),
            'groupItems' => $this->getGroupItems()->getArrayCopy(),
        ];
    }
}
