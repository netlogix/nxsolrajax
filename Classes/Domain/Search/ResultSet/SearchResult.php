<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

class SearchResult extends \ApacheSolrForTypo3\Solrfluid\Domain\Search\ResultSet\SearchResult implements \JsonSerializable
{

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_fields['type_stringS'];
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