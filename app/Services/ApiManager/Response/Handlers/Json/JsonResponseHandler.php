<?php
namespace App\Services\ApiManager\Response\Handlers\Json;

use App\Services\ApiManager\Response\Handlers\ResponseHandler;

class JsonResponseHandler extends ResponseHandler
{
    public function getListItems()
    {
        $this->setResponseKeysArray();
        $itemList = $this->getItemList();
        $build =  $this->buildListItems($itemList);
        return $build;
    }

    public function getListData()
    {
        $this->setResponseKeysArray();
        return $this->buildParentListItems($this->getParentItemList());
    }

    /**
     * @param mixed $responseArray
     */
    public function setResponseArray($responseArray): void
    {
        $this->responseArray = $responseArray;
    }
}
