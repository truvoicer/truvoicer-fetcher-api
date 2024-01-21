<?php

namespace App\Services\ApiManager\Response\Handlers\Xml;

use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Services\ApiManager\Response\Handlers\ResponseHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class XmlResponseHandler extends ResponseHandler
{

    public function getListItems()
    {
        $itemList = $this->getItemList();
        $buildListItems =  $this->buildListItems($itemList);
        return $buildListItems;
    }

    public function getListData()
    {
        return $this->buildParentListItems($this->getParentItemList());
    }

    protected function buildListItems(array $itemList)
    {
        return array_map(function ($item) {
            $itemList = [];
            foreach ($this->responseKeysArray as $responseKey) {
                if (!$responseKey->srResponseKey instanceof SrResponseKey) {
                    continue;
                }
                if (!$responseKey->srResponseKey->list_item) {
                    continue;
                }
                $name = $responseKey->name;
                $srResponseKey = $responseKey->srResponseKey;
                $getAttribute = $this->getAttribute($srResponseKey->value, $item);
                if ($getAttribute && $srResponseKey->show_in_response) {
                    $itemList[$name] = $getAttribute;
                } elseif ($srResponseKey->show_in_response) {
                    $itemList[$name] = $this->buildList($item, $srResponseKey);
                }

            }
            $itemList["provider"] = $this->provider->name;
            return $itemList;
        }, $itemList);
    }


    private function getAttribute(string $keyValue, $itemArray = null)
    {
        if ($itemArray === null) {
            return false;
        }
        if (strpos($keyValue, "attribute." === false)) {
            return false;
        }
        $keyArray = explode(".", $keyValue);

        if (isset($keyArray[2])) {
            return $itemArray["attributes"][$keyArray[2]];
        }
        return false;
    }

    /**
     * @param string $responseContent
     */
    public function setResponseArray(string $responseContent): void
    {
        if ($this->xmlService->checkXmlErrors($responseContent)) {
            throw new BadRequestHttpException("item_request_error");
        }
        $responseKeyValue = $this->findSrResponseKeyValueInArray('items_array');
        if (empty($responseKeyValue)) {
            throw new BadRequestHttpException("Response key value is empty.");
        }
        $itemRepeaterKeyValue = $this->findSrResponseKeyValueInArray('item_repeater_key');
        if (empty($itemRepeaterKeyValue)) {
            throw new BadRequestHttpException("item_repeater_key value is empty.");
        }
        $this->responseArray = $this->xmlService->convertXmlToArray(
            $responseContent,
            $this->filterItemsArrayValue($responseKeyValue)["value"],
            $this->filterItemsArrayValue($responseKeyValue)["brackets"],
            $this->filterItemsArrayValue($itemRepeaterKeyValue)["value"]
        );
    }
}
