<?php

namespace App\Services\ApiManager\Response\Handlers\Xml;

use App\Enums\Api\ApiListKey;
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

        if (empty($this->sr->{ApiListKey::LIST_KEY->value})) {
            throw new BadRequestHttpException(ApiListKey::LIST_KEY->value . " is empty.");
        }
        $itemRepeaterKeyValue = $this->sr->{ApiListKey::LIST_ITEM_REPEATER_KEY->value};
        if (empty($itemRepeaterKeyValue)) {
            throw new BadRequestHttpException(
                sprintf(
                    "%s value is empty. %s: %s | %s: %s",
                    ApiListKey::LIST_ITEM_REPEATER_KEY->value,
                    ApiListKey::LIST_ITEM_REPEATER_KEY->value,
                    $itemRepeaterKeyValue,
                    ApiListKey::LIST_KEY->value,
                    $this->sr->{ApiListKey::LIST_KEY->value}
                    )
            );
        }
        $this->responseArray = $this->xmlService->parseXmlContent(
            $responseContent,
            $this->filterItemsArrayValue($this->sr->{ApiListKey::LIST_KEY->value})["value"],
            $this->filterItemsArrayValue($this->sr->{ApiListKey::LIST_KEY->value})["brackets"],
            $this->filterItemsArrayValue($this->sr->{ApiListKey::LIST_ITEM_REPEATER_KEY->value})["value"]
        );
    }

    public function convertXmlToArray(string $responseContent): array
    {
        return $this->xmlService->convertXmlToArray($responseContent);
    }
}
