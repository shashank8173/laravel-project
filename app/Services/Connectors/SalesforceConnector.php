<?php

namespace App\Services\Connectors;

class SalesforceConnector extends AbstractEmployeeConnector
{
    public function type(): string
    {
        return 'salesforce';
    }

    public function label(): string
    {
        return 'Salesforce';
    }

    public function description(): string
    {
        return 'Receive Salesforce Flow / Apex / middleware employee payloads. Optional pull from a middleware URL.';
    }

    public function defaultFieldMap(): array
    {
        return [
            'Id' => 'external_id',
            'EmployeeNumber' => 'emp_id',
            'FirstName' => 'fname',
            'LastName' => 'lname',
            'Email' => 'office_email',
            'MobilePhone' => 'mobile1',
            'Phone' => 'mobile1',
            'Department' => 'department',
            'Title' => 'job_title',
            'HireDate__c' => 'doj',
            'StartDate' => 'doj',
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
            throw new \RuntimeException('Salesforce pull needs config.pull_url (Flow HTTP or middleware) returning JSON employees[].');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => trim((string) ($config['pull_auth'] ?? '')),
            'Accept' => 'application/json',
        ])->timeout(60)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Salesforce pull failed: '.$response->body());
        }

        return $this->extractRecords($response->json() ?? []);
    }

    public function samplePayload(): array
    {
        return [
            'employees' => [[
                'Id' => '003XX000004TMM2',
                'EmployeeNumber' => 'SF442',
                'FirstName' => 'Priya',
                'LastName' => 'Nair',
                'Email' => 'priya.nair@example.com',
                'MobilePhone' => '9900099000',
                'Department' => 'Finance',
                'Title' => 'Accountant',
                'HireDate__c' => '2022-08-20',
            ]],
        ];
    }
}
