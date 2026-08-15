<?php

namespace App\Services\Connectors;

class SuccessFactorsConnector extends AbstractEmployeeConnector
{
    public function type(): string
    {
        return 'successfactors';
    }

    public function label(): string
    {
        return 'SAP SuccessFactors';
    }

    public function description(): string
    {
        return 'Receive SuccessFactors OData / Integration Center employee events via webhook or middleware pull URL.';
    }

    public function defaultFieldMap(): array
    {
        return [
            'personIdExternal' => 'external_id',
            'userId' => 'emp_id',
            'firstName' => 'fname',
            'lastName' => 'lname',
            'email' => 'office_email',
            'cellPhone' => 'mobile1',
            'department' => 'department',
            'jobTitle' => 'job_title',
            'startDate' => 'doj',
            'dateOfBirth' => 'dob',
        ];
    }

    public function supportsPull(): bool
    {
        return true;
    }

    public function pullRecords(array $config): array
    {
        $url = trim((string) ($config['pull_url'] ?? ''));
        if ($url === '') {
            throw new \RuntimeException('SuccessFactors pull needs config.pull_url (Integration Center / middleware JSON).');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => trim((string) ($config['pull_auth'] ?? '')),
            'Accept' => 'application/json',
        ])->timeout(60)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('SuccessFactors pull failed: '.$response->body());
        }

        return $this->extractRecords($response->json() ?? []);
    }

    public function samplePayload(): array
    {
        return [
            'd' => [
                'results' => [[
                    'personIdExternal' => 'SF-991',
                    'userId' => 'U991',
                    'firstName' => 'Amit',
                    'lastName' => 'Singh',
                    'email' => 'amit.singh@example.com',
                    'cellPhone' => '9123456780',
                    'department' => 'Operations',
                    'jobTitle' => 'Manager',
                    'startDate' => '/Date(1711929600000)/',
                ]],
            ],
        ];
    }

    public function transformRecord(array $record, array $fieldMap): array
    {
        $out = parent::transformRecord($record, $fieldMap);

        if (! empty($out['doj']) && is_string($out['doj']) && preg_match('/\/Date\((\d+)\)\//', $out['doj'], $m)) {
            $out['doj'] = date('Y-m-d', (int) floor(((int) $m[1]) / 1000));
        }
        if (! empty($out['dob']) && is_string($out['dob']) && preg_match('/\/Date\((\d+)\)\//', $out['dob'], $m)) {
            $out['dob'] = date('Y-m-d', (int) floor(((int) $m[1]) / 1000));
        }

        return $out;
    }
}
