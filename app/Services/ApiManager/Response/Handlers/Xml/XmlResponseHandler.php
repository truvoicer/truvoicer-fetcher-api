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
        return array_values(
            $this->buildListItems(
                $this->getItemList()
            )
        );
    }

    private function buildArray(array $array)
    {
        $buildArray = [];
        foreach ($array as $item) {
            array_push($buildArray, $item);
        }
        return $buildArray;
    }

    public function getListData()
    {
        return $this->buildParentListItems($this->getParentItemList());
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
