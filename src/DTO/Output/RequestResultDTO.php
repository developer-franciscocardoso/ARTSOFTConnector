<?php

declare(strict_types=1);

namespace Feijosul\ArtsoftConnector\DTO\Output;

final readonly class RequestResultDTO
{
    public function __construct(
        public bool $success,
        public string $service,
        public string $company,
        public string $timestamp,
        public mixed $data = null,
        public ?string $error = null,
        public mixed $rawResponse = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'service' => $this->service,
            'company' => $this->company,
            'timestamp' => $this->timestamp,
        ];

        if ($this->success) {
            $result['data'] = $this->data;
            return $result;
        }

        $result['error'] = $this->error ?? 'Unknown error';
        if ($this->rawResponse !== null) {
            $result['raw_response'] = $this->rawResponse;
        }

        return $result;
    }
}
