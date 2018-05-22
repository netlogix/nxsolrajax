<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

class SearchResult extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult implements \JsonSerializable
{

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_fields['type'];
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->_fields['highlightedContent'] ?: $this->_fields['teaser'];
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
            'url' => $this->getUrl(),
        ];
    }

}
