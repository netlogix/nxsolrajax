<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use Override;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult as SolrSearchResult;
use JsonSerializable;

class SearchResult extends SolrSearchResult implements JsonSerializable
{

    #[Override]
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

    #[Override]
    public function getContent(): string
    {
        return $this->fields['highlightedContent'] ?? $this->fields['abstract'] ?? '';
    }

    public function getImage(): string
    {
        return $this->fields['image'] ?? '';
    }

}
