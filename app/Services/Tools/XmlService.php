<?php

namespace App\Services\Tools;

use SimpleXMLIterator;

class XmlService
{
    /**
     * Check for errors
     * @param string $xmlContent
     * @return bool
     */
    public function checkXmlErrors(string $xmlContent)
    {
        $xmlElement = new \SimpleXMLElement($xmlContent);
        if ($xmlElement->getName() == "error") {
            return true;
        }
        return false;
    }

    /**
     * Convert xml content to array
     * @param string $xmlContent
     * @param string $childKey
     * @param bool $parentItemArray
     * @return array
     */
    public function convertXmlToArray(string $xmlContent, string $childKey, bool $parentItemArray, string $itemRepeaterKey)
    {
        $simpleXMLIterator = new SimpleXmlIterator($xmlContent, null, false);
        $xmlarray = $this->getXmlArray($simpleXMLIterator, $childKey, $parentItemArray, $itemRepeaterKey);
        return $xmlarray;
    }

    /**
     * Builds xml array data for api xml response
     * @param SimpleXMLIterator $xmlIterator
     * @param string $childKey
     * @param bool $parentItemArray
     * @return array
     */
    public function getXmlArray(SimpleXMLIterator $xmlIterator, string $childKey, bool $parentItemArray, string $itemRepeaterKey)
    {
        $items = [];
        $extra = [];
        $rootItem = false;
        $i = 0;

        for ($xmlIterator->rewind(); $xmlIterator->valid(); $xmlIterator->next()) {
            if ($xmlIterator->getName() === $childKey) {
                $items[$i] = $xmlIterator;
                $rootItem = true;
                break;
            }
            if ($xmlIterator->hasChildren()) {
                if ($xmlIterator->key() === $childKey) {
                    $items[$i] = $xmlIterator->current();
                    $i++;
                }
            } else {
                if ($xmlIterator->attributes()) {
                    foreach ($xmlIterator->attributes() as $key => $value) {
                        $extra["attributes"][$key] = strval($value);
                    }
                }
                $extra[$xmlIterator->key()] = strval($xmlIterator->current());
            }
        }
        $items = array_map(function ($iterator) use ($itemRepeaterKey) {
            return $this->xmlToArrayIterator($iterator, $itemRepeaterKey);
        }, $items);

        $xmlArray = $extra;

        if ($rootItem || $parentItemArray) {
            $xmlArray[$childKey] = $items;
        } elseif (is_array($items) && count($items) === 1 && array_key_exists(0, $items) && is_array($items[0])) {
            $xmlArray[$childKey] = $items[0];
        }
        return $xmlArray;
    }

    /**
     * Iterate over xml nodes, build array of nodes and children
     * @param SimpleXMLIterator $xmlIterator
     * @return array
     */
    public function xmlToArrayIterator(SimpleXMLIterator $xmlIterator, string $itemRepeaterKey, ?bool $parentItemArray = false)
    {
        $a = array();
        $i = 0;
        for ($xmlIterator->rewind(); $xmlIterator->valid(); $xmlIterator->next()) {
            if (!$parentItemArray && $xmlIterator->key() !== $itemRepeaterKey) {
                continue;
            }
            if ($xmlIterator->hasChildren()) {
                if (array_key_exists($xmlIterator->key(), $a)) {
                    $a[$xmlIterator->key() . $i] = $this->getNamespacedNodes(
                        $xmlIterator->current(),
                        $this->xmlToArrayIterator($xmlIterator->current(), $itemRepeaterKey, true)
                    );
                } else {
                    $a[$xmlIterator->key()] = $this->getNamespacedNodes(
                        $xmlIterator->current(),
                        $this->xmlToArrayIterator($xmlIterator->current(), $itemRepeaterKey, true)
                    );
                }
            } else {
                if ($xmlIterator->current()->attributes()) {
                    $a[$xmlIterator->key()] = [];
                    foreach ($xmlIterator->current()->attributes() as $key => $value) {
                        $a[$xmlIterator->key()][$key] = strval($value);
                    }
                } else {
                    $a[$xmlIterator->key()] = strval($xmlIterator->current());
                }
            }
            $i++;
        }
        return $a;
    }

    /**
     * Add any namespaced values to array
     * @param SimpleXMLIterator $xmlIterator
     * @param $data
     * @return mixed
     */
    private function getNamespacedNodes(SimpleXMLIterator $xmlIterator, $data)
    {
        $namespaces = $xmlIterator->getNamespaces(true);
        foreach ($namespaces as $key => $namespace) {
            $data[$key] = $this->xmlToArrayIterator($xmlIterator, $key);
            foreach ($xmlIterator->children($key, true) as $childKey => $child) {
                if ($child->attributes()) {
                    $data[$key][$childKey] = [];
                    foreach ($child->attributes() as $attKey => $value) {
                        $data[$key][$childKey][$attKey] = strval($value);
                    }
                    $contentValue = strval($child);
                    if (isset($contentValue) && $contentValue !== "") {
                        $data[$key][$childKey]['value'] = $contentValue;
                    }
                } else {
                    $contentValue = strval($child);
                    if (isset($contentValue) && $contentValue !== "") {
                        $data[$key][$childKey] = $contentValue;
                    }
                }

            }
        }
        return $data;
    }
}
