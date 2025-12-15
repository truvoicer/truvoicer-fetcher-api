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

        if (empty($this->sr->items_array_key)) {
            throw new BadRequestHttpException("items_array_key is empty.");
        }
        $itemRepeaterKeyValue = $this->findSrResponseKeyValueInArray('item_repeater_key');
        if (empty($itemRepeaterKeyValue)) {
            throw new BadRequestHttpException("item_repeater_key value is empty.");
        }
        $this->responseArray = $this->xmlService->parseXmlContent(
            $responseContent,
            $this->filterItemsArrayValue($this->sr->items_array_key)["value"],
            $this->filterItemsArrayValue($this->sr->items_array_key)["brackets"],
            $this->filterItemsArrayValue($this->sr->item_repeater_key)["value"]
        );
    }

    public function convertXmlToArray(string $responseContent): array
    {
        return $this->xmlService->convertXmlToArray($responseContent);
    }
}
