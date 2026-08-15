<?php

namespace App\Services\Connectors;

abstract class AbstractEmployeeConnector implements EmployeeConnectorInterface
{
    public function supportsPull(): bool
    {
        return false;
    }

    public function pullRecords(array $config): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, string>  $fieldMap
     * @return array<string, mixed>
     */
    public function transformRecord(array $record, array $fieldMap): array
    {
        $flat = $this->flatten($record);
        $out = [];

        foreach ($fieldMap as $source => $target) {
            $source = trim((string) $source);
            $target = trim((string) $target);
            if ($source === '' || $target === '') {
                continue;
            }
            if (array_key_exists($source, $flat) && $flat[$source] !== null && $flat[$source] !== '') {
                $out[$target] = is_scalar($flat[$source]) ? (string) $flat[$source] : $flat[$source];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function extractRecords(array $payload): array
    {
        if ($this->looksLikeEmployee($payload)) {
            return [$payload];
        }

        foreach (['employees', 'data', 'value', 'results', 'Records', 'd'] as $key) {
            if (! empty($payload[$key]) && is_array($payload[$key])) {
                $list = $payload[$key];
                // OData { d: { results: [] } }
                if (isset($list['results']) && is_array($list['results'])) {
                    $list = $list['results'];
                }
                if (array_is_list($list)) {
                    return array_values(array_filter($list, 'is_array'));
                }
            }
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function looksLikeEmployee(array $row): bool
    {
        $keys = array_change_key_case($row, CASE_LOWER);

        return isset($keys['email'])
            || isset($keys['office_email'])
            || isset($keys['workemail'])
            || isset($keys['firstname'])
            || isset($keys['fname'])
            || isset($keys['employeenumber'])
            || isset($keys['worker']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string  $prefix
     * @return array<string, mixed>
     */
    protected function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value) && $this->isAssoc($value)) {
                $out += $this->flatten($value, $path);
            } else {
                $out[$path] = $value;
                // Also keep top-level key without prefix for simple maps.
                if ($prefix === '') {
                    $out[(string) $key] = $value;
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<mixed>  $arr
     */
    protected function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
