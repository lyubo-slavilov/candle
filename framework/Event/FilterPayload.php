<?php

namespace Candle\Event;

class FilterPayload
{
    private $filterName;
    private $payload = array();
    private $payloadChanged = false;
    private $paramsChanged = array();

    public function __construct($filterName, $payload) {
        $this->filterName = $filterName;
        $this->payload = $payload;
        $this->payloadChanged = false;
    }

    public function getFilterName()
    {
        return $this->filterName;
    }

    public function asArray()
    {
        return $this->payload;
    }

    public function get($paramName, $default = null) {

        if (isset($this->payload[$paramName])) {
            $result = $this->payload[$paramName];
        } else {
            $result = $default;
        }
        return $result;
    }

    public function set($paramName, $paramValue) {
        $this->payload[$paramName] = $paramValue;
        $this->paramsChanged[$paramName] = true;
        $this->payloadChanged = true;
    }

    public function isChanged()
    {
        return $this->payloadChanged;
    }

    public function isParamChanged($paramName) {
        return isset($this->paramsChanged[$paramName]) && $this->paramsChanged[$paramName];
    }

}

