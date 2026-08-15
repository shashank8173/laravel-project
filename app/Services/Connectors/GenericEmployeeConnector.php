<?php

namespace App\Services\Connectors;

/**
 * Vendor-agnostic connector: any third-party tool can POST JSON employees.
 * Field map is dynamic. If payload already uses HRM keys, it is accepted as-is.
 */
class GenericEmployeeConnector extends AbstractEmployeeConnector
{
    public function type(): string
    {
        return 'generic';
    }

    public function label(): string
    {
        return 'Generic Connector';
    }

    public function description(): string
    {
        return 'Accept employee JSON from any third-party tool (Zapier, Make, middleware, custom API). No vendor lock-in.';
    }

    public function defaultFieldMap(): array
    {
        return [
            'first_name' => 'fname',
            'FirstName' => 'fname',
            'last_name' => 'lname',
            'LastName' => 'lname',
            'email' => 'office_email',
            'Email' => 'office_email',
            'work_email' => 'office_email',
            'office_email' => 'office_email',
            'employee_id' => 'external_id',
            'EmployeeID' => 'external_id',
            'Id' => 'external_id',
            'id' => 'external_id',
            'employee_code' => 'emp_id',
            'EmployeeNumber' => 'emp_id',
            'phone' => 'mobile1',
            'mobile' => 'mobile1',
            'MobilePhone' => 'mobile1',
            'department' => 'department',
            'Department' => 'department',
            'designation' => 'designation',
            'Designation' => 'designation',
            'job_title' => 'job_title',
            'Title' => 'job_title',
            'date_of_joining' => 'doj',
            'doj' => 'doj',
            'HireDate__c' => 'doj',
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
            throw new \RuntimeException('Set a Pull URL that returns JSON employees (any third-party middleware).');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => trim((string) ($config['pull_auth'] ?? '')),
            'Accept' => 'application/json',
        ])->timeout(60)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Pull failed: '.$response->body());
        }

        return $this->extractRecords($response->json() ?? []);
    }

    public function samplePayload(): array
    {
        return [
            'employees' => [[
                'employee_id' => 'EXT-1001',
                'employee_code' => 'EMP1001',
                'first_name' => 'Ravi',
                'last_name' => 'Kumar',
                'email' => 'ravi.kumar@example.com',
                'phone' => '9999900001',
                'department' => 'Sales',
                'designation' => 'Executive',
                'job_title' => 'Sales Executive',
                'date_of_joining' => '2024-01-10',
            ]],
        ];
    }

    public function transformRecord(array $record, array $fieldMap): array
    {
        // If payload already looks like HRM fields, keep dynamic pass-through.
        $hrmKeys = ['fname', 'office_email', 'external_id', 'emp_id'];
        $hasHrm = false;
        foreach ($hrmKeys as $key) {
            if (! empty($record[$key])) {
                $hasHrm = true;
                break;
            }
        }

        if ($hasHrm && $fieldMap === []) {
            return $record;
        }

        $mapped = parent::transformRecord($record, $fieldMap);

        // Merge unmapped HRM keys already present in source (dynamic).
        foreach (['fname', 'lname', 'office_email', 'email', 'mobile1', 'emp_id', 'external_id', 'department', 'designation', 'job_title', 'doj', 'dob', 'salary', 'role', 'status'] as $key) {
            if (! isset($mapped[$key]) && isset($record[$key]) && $record[$key] !== '' && $record[$key] !== null) {
                $mapped[$key] = $record[$key];
            }
        }

        return $mapped;
    }
}
