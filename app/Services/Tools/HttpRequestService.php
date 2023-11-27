<?php
namespace App\Services\Tools;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HttpRequestService
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function getRequestData(Request $request, $array = false) {
        if ($request->getContentType() == "json") {
            return json_decode($request->getContent(), $array);
        }
        return $request->request->all();
    }

    public function validateData($entity) {
        $errors = $this->validator->validate($entity);

        if (count($errors) === 0) {
            return true;
        }
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = sprintf("Field: (%s) - %s", $error->getPropertyPath(), $error->getMessage());
        }
        throw new BadRequestHttpException("Validation failed. " . implode(",", $errorMessages));
    }
}
