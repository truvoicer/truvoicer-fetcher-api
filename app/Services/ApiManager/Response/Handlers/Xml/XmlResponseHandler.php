<?php

namespace App\Services\ApiManager\Response\Handlers\Xml;

use App\Services\ApiManager\Response\Handlers\ResponseHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class XmlResponseHandler extends ResponseHandler
{

    public function getListItems()
    {
        return $this->buildListItems($this->getItemList());
    }

    public function getListData()
    {
        return $this->buildParentListItems($this->getParentItemList());
    }

    protected function buildListItems(array $itemList)
    {
        return array_map(function ($item) {
            $itemList = [];
            foreach ($this->responseKeysArray as $keys) {
                $getKey = $this->getRequestResponseKeyByName($keys);
                if ($getKey !== null && $getKey->getListItem()) {
                    $getAttribute = $this->getAttribute($getKey->getResponseKeyValue(), $item);
                    if ($getAttribute && $getKey->getShowInResponse()) {
                        $itemList[$keys] = $getAttribute;
                    } elseif ($getKey->getShowInResponse()) {
                        $itemList[$keys] = $this->buildList($item, $getKey);
                    }
                }
            }
            $itemList["provider"] = $this->provider->getProviderName();
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
        $itemsArrayString = $this->getRequestResponseKeyByName($this->responseKeysArray['ITEMS_ARRAY'])->getResponseKeyValue();
        $this->responseArray = $this->xmlService->convertXmlToArray($responseContent,
            $this->filterItemsArrayValue($itemsArrayString)["value"],
            $this->filterItemsArrayValue($itemsArrayString)["brackets"]
        );
    }
}
