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

    public function convertXmlToArray(string $xmlContent)
    {
//        $simpleXMLIterator = new SimpleXmlIterator($xmlContent, null, false);
        return $this->xml2array($xmlContent);
    }
    private function xml2array($contents, $get_attributes = 1, $priority = 'tag')
    {
        if (!$contents) return array();
        if (!function_exists('xml_parser_create')) {
            // print "'xml_parser_create()' function not found!";
            return array();
        }
        // Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); // http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents) , $xml_values);
        xml_parser_free($parser);
        if (!$xml_values) return; //Hmm...
        // Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        $current = & $xml_array; //Refference
        // Go through the tags.
        $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
        foreach($xml_values as $data) {
            unset($attributes, $value); //Remove existing values, or there will be trouble
            // This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data); //We could use the array by itself, but this cooler.
            $result = array();
            $attributes_data = array();
            if (isset($value)) {
                if ($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }
            // Set the attributes too.
            if (isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if ( $attr == 'ResStatus' ) {
                        $current[$attr][] = $val;
                    }
                    if ($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
            // See tag status and do the needed.
            //echo"<br/> Type:".$type;
            if ($type == "open") { //The starting of the tag '<tag>'
                $parent[$level - 1] = & $current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data) $current[$tag . '_attr'] = $attributes_data;
                    //print_r($current[$tag . '_attr']);
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = & $current[$tag];
                }
                else { //There was another element with the same tag name
                    if (isset($current[$tag][0])) { //If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else { //This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = & $current[$tag][$last_item_index];
                }
            }
            elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
                // See if the key is already taken.
                if (!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data) $current[$tag . '_attr'] = $attributes_data;
                }
                else { //If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) and is_array($current[$tag])) { //If it is already an array...
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else { //If it is not an array...
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                    }
                }
            }
            elseif ($type == 'close') { //End of tag '</tag>'
                $current = & $parent[$level - 1];
            }
        }
        return ($xml_array);
    }
//    public function convertXmlToArrayIterator(SimpleXMLIterator $xmlIterator): array
//    {
//        $items = [];
//        $xmlArray = [];
//        $rootItem = false;
//        $i = 0;
//
//        for ($xmlIterator->rewind(); $xmlIterator->valid(); $xmlIterator->next()) {
//            if ($xmlIterator->getName() === $childKey) {
//                $items[$i] = $xmlIterator;
//                $rootItem = true;
//                break;
//            }
//            if ($xmlIterator->hasChildren()) {
//                if ($xmlIterator->key() === $childKey) {
//                    $items[$i] = $xmlIterator->current();
//                    $i++;
//                }
//            }
//        }
//        $items = array_map(function ($iterator) use ($itemRepeaterKey) {
//            return $this->xmlToArrayIterator($iterator, $itemRepeaterKey);
//        }, $items);
//
//        if ($rootItem || $parentItemArray) {
//            $xmlArray[$childKey] = $items;
//        } elseif (count($items) === 1 && array_key_exists(0, $items) && is_array($items[0])) {
//            $xmlArray[$childKey] = $items[0];
//        }
//        return $xmlArray;
//    }
    /**
     * Convert xml content to array
     * @param string $xmlContent
     * @param string $childKey
     * @param bool $parentItemArray
     * @return array
     */
    public function parseXmlContent(string $xmlContent, string $childKey, bool $parentItemArray, string $itemRepeaterKey)
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

        // if ($rootItem || $parentItemArray) {
        //     $xmlArray[$childKey] = $items;
        // } elseif (count($items) === 1 && array_key_exists(0, $items) && is_array($items[0])) {
        //     $xmlArray[$childKey] = $items[0];
        // }
        if (count($items) === 1 && array_key_exists(0, $items) && is_array($items[0])) {
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
