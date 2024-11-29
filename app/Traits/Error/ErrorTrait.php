<?php

namespace App\Traits\Error;

trait ErrorTrait
{
    private array $errors;
    protected ?bool $throwException = true;

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return count($this->errors ?? []) > 0;
    }

    /**
     * @param string $code
     * @param string $message
     * @param array|null $data
     */
    public function addError(string $code, string $message, ?array $data = []): void
    {
        $error = [
            'code' => $code,
            'message' => $message
        ];
        if (count($data)) {
            $error['data'] = $data;
        }
        $this->errors[] = $error;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors ?? [];
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function setThrowException(?bool $throwException): self
    {
        $this->throwException = $throwException;
        return $this;
    }

}
