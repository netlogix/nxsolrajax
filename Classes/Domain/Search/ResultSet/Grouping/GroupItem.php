<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping;

use JsonSerializable;

class GroupItem extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItem implements JsonSerializable
{
    private $groupLabel;
    private $groupUrl;

    protected function getGroupLabel(): string
    {
        return $this->groupLabel ?? $this->getGroupValue();
    }

    public function setGroupLabel($groupLabel): void
    {
        $this->groupLabel = $groupLabel;
    }

    public function getGroupUrl(): string
    {
        return $this->groupUrl ?? '';
    }

    public function setGroupUrl($groupUrl): void
    {
        $this->groupUrl = $groupUrl;
    }

    public function jsonSerialize()
    {
        return [
            'label' => $this->getGroupLabel(),
            'name' => $this->getGroupValue(),
            'totalResults' => $this->getAllResultCount(),
            'start' => $this->getStart(),
            'maxScore' => $this->getMaximumScore(),
            'url' => $this->getGroupUrl(),
            'items' => $this->getSearchResults()->getArrayCopy(),
        ];
    }
}
