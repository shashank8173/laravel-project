<?php

namespace App\Services\Connectors;

use App\Models\Connector;

interface EmployeeConnectorInterface
{
    public function type(): string;

    public function label(): string;

    public function description(): string;

    /**
     * Default source→target field map.
     *
     * @return array<string, string>
     */
    public function defaultFieldMap(): array;

    /**
     * Transform one vendor employee record into HRM employee payload.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, string>  $fieldMap
     * @return array<string, mixed>
     */
    public function transformRecord(array $record, array $fieldMap): array;

    /**
     * Extract employee records from webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function extractRecords(array $payload): array;

    /**
     * Optional remote pull. Return list of vendor records.
     *
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    public function pullRecords(array $config): array;

    public function supportsPull(): bool;

    /**
     * @return array<string, mixed>
     */
    public function samplePayload(): array;
}
