<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult as SolrSearchResult;
use JsonSerializable;

class SearchResult extends SolrSearchResult implements JsonSerializable
{

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'image' => $this->getImage(),
            'url' => $this->getUrl(),
        ];
    }

    public function getContent(): string
    {
        return $this->fields['highlightedContent'] ?? $this->fields['abstract'];
    }

    public function getImage(): string
    {
        return $this->fields['image'] ?? '';
    }

}
