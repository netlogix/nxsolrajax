<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group as SolrGroup;
use JsonSerializable;

class Group extends SolrGroup implements JsonSerializable
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
