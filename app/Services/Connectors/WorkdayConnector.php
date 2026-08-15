<?php

namespace App\Services\Connectors;

class WorkdayConnector extends AbstractEmployeeConnector
{
    public function type(): string
    {
        return 'workday';
    }

    public function label(): string
    {
        return 'Workday HCM';
    }

    public function description(): string
    {
        return 'Receive Workday Worker / Integration events via webhook (Studio / middleware). Pull uses your middleware URL.';
    }

    public function defaultFieldMap(): array
    {
        return [
            'Worker_ID' => 'external_id',
            'worker.workerId' => 'external_id',
            'Employee_ID' => 'emp_id',
            'Legal_Name_Data.Name_Detail_Data.First_Name' => 'fname',
            'firstName' => 'fname',
            'Legal_Name_Data.Name_Detail_Data.Last_Name' => 'lname',
            'lastName' => 'lname',
            'Email_Address' => 'office_email',
            'email' => 'office_email',
            'Phone_Number' => 'mobile1',
            'phone' => 'mobile1',
            'Organization_Name' => 'department',
            'department' => 'department',
            'Job_Title' => 'job_title',
            'jobTitle' => 'job_title',
            'Hire_Date' => 'doj',
            'hireDate' => 'doj',
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
            throw new \RuntimeException('Workday pull needs config.pull_url pointing to your middleware that returns JSON employees[].');
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => trim((string) ($config['pull_auth'] ?? '')),
            'Accept' => 'application/json',
        ])->timeout(60)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Workday pull failed: '.$response->body());
        }

        return $this->extractRecords($response->json() ?? []);
    }

    public function samplePayload(): array
    {
        return [
            'employees' => [[
                'Worker_ID' => 'WD7788',
                'firstName' => 'Rahul',
                'lastName' => 'Mehta',
                'email' => 'rahul.mehta@example.com',
                'phone' => '9811100110',
                'department' => 'Engineering',
                'jobTitle' => 'Software Engineer',
                'hireDate' => '2023-11-15',
            ]],
        ];
    }
}
