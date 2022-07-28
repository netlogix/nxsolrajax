<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

class SearchResult extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult implements \JsonSerializable
{

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->fields['highlightedContent'] ?? $this->fields['abstract'];
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->fields['image'] ?? '';
    }

    /**
     * @return array
     */
    public function jsonSerialize()
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

}
