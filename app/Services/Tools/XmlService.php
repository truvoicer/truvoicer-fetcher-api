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
        $xmlArray = [];
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
            }
        }
        $items = array_map(function ($iterator) use ($itemRepeaterKey) {
            return $this->xmlToArrayIterator($iterator, $itemRepeaterKey);
        }, $items);

        if ($rootItem || $parentItemArray) {
            $xmlArray[$childKey] = $items;
        } elseif (count($items) === 1 && array_key_exists(0, $items) && is_array($items[0])) {
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
                    $attributes = [];
                    foreach ($xmlIterator->current()->attributes() as $key => $value) {
                        $attrib = [];
                        $contentValue = strval($value);
                        $attrib[] = [
                            'key' => $key,
                            'value' => $contentValue
                        ];
                        if (isset($contentValue) && $contentValue !== "") {
                            $attrib[] = [
                                'key' => 'value',
                                'value' => strval($xmlIterator->current())
                            ];
                        }
                        $attributes[] = $attrib;
                    }
                    $buildAttribute = $this->buildAttributes($attributes);
                    if (!array_key_exists($xmlIterator->key(), $a)) {
                        $a[$xmlIterator->key()] = [
                            'xml_value_type' => 'attribute'
                        ];
                    }
                    if (!array_key_exists('attributes', $a[$xmlIterator->key()])) {
                        $a[$xmlIterator->key()]['attributes'] = [];
                    }
                    if (!array_key_exists('values', $a[$xmlIterator->key()])) {
                        $a[$xmlIterator->key()]['values'] = [];
                        if (count($buildAttribute['values']) === 1) {
                            $a[$xmlIterator->key()]['values'] = $buildAttribute['values'][array_key_first($buildAttribute['values'])];
                        }
                    } else {
                        $getExistingValue = $a[$xmlIterator->key()]['values'];
                        if (is_array($getExistingValue)) {
                            $a[$xmlIterator->key()]['values'] = array_merge(
                                $a[$xmlIterator->key()]['values'],
                                $buildAttribute['values']
                            );
                        } else {
                            $a[$xmlIterator->key()]['values'] = $buildAttribute['values'];
                        }
                    }
                    $a[$xmlIterator->key()]['attributes'] = array_merge(
                        $a[$xmlIterator->key()]['attributes'],
                        $buildAttribute['attributes']
                    );
                } else {
                    $a[$xmlIterator->key()] = strval($xmlIterator->current());
                }
            }
            $i++;
        }
        return $a;
    }

    private function buildAttributes(array $data)
    {
        $nonValueAtts = [];
        $valueAtts = [];
        foreach ($data as $attribute) {
            $nonValueAtts = array_merge(
                $nonValueAtts,
                array_filter($attribute, function ($att) {
                    return $att['key'] !== 'value';
                })
            );
            $valueAtts = array_merge(
                $valueAtts,
                array_filter($attribute, function ($att) {
                    return $att['key'] === 'value';
                })
            );
        }
        $atts = [ 'attributes' => array_combine(
            array_column($nonValueAtts, 'key'),
            array_column($nonValueAtts, 'value')
        ), 'values' => []];
        if (count($valueAtts) === 1) {
            $atts['values'] = array_map(function ($att) {
                return $att['value'];
            }, $valueAtts);
        } else if (count($valueAtts) > 1) {
            $atts['values'] = array_map(function ($att) {
                return $att['value'];
            }, $valueAtts);
        }
        return $atts;
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
