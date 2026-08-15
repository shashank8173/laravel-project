<?php

namespace App\Services\Connectors;

use Illuminate\Support\Facades\Http;

class ZohoPeopleConnector extends AbstractEmployeeConnector
{
    public function type(): string
    {
        return 'zoho';
    }

    public function label(): string
    {
        return 'Zoho People';
    }

    public function description(): string
    {
        return 'Sync employees from Zoho People via webhook or API pull (OAuth refresh token).';
    }

    public function defaultFieldMap(): array
    {
        return [
            'EmployeeID' => 'external_id',
            'EmployeeID.ID' => 'external_id',
            'FirstName' => 'fname',
            'LastName' => 'lname',
            'EmailID' => 'office_email',
            'Mobile' => 'mobile1',
            'Department' => 'department',
            'Designation' => 'designation',
            'Dateofjoining' => 'doj',
            'Date_of_birth' => 'dob',
            'Employeestatus' => 'status_label',
        ];
    }

    public function supportsPull(): bool
    {
        return true;
    }

    public function pullRecords(array $config): array
    {
        $accessToken = trim((string) ($config['access_token'] ?? ''));
        $refreshToken = trim((string) ($config['refresh_token'] ?? ''));
        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));
        $accountsUrl = rtrim((string) ($config['accounts_url'] ?? 'https://accounts.zoho.in'), '/');
        $peopleUrl = rtrim((string) ($config['people_url'] ?? 'https://people.zoho.in'), '/');

        if ($accessToken === '' && $refreshToken !== '' && $clientId !== '' && $clientSecret !== '') {
            $tokenRes = Http::asForm()->post($accountsUrl.'/oauth/v2/token', [
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
            ]);
            if (! $tokenRes->successful()) {
                throw new \RuntimeException('Zoho token refresh failed: '.$tokenRes->body());
            }
            $accessToken = (string) ($tokenRes->json('access_token') ?? '');
        }

        if ($accessToken === '') {
            throw new \RuntimeException('Zoho pull needs access_token or refresh_token + client_id/secret.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken '.$accessToken,
        ])->get($peopleUrl.'/people/api/forms/employee/getRecords', [
            'sIndex' => 1,
            'limit' => (int) ($config['pull_limit'] ?? 200),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Zoho employee pull failed: '.$response->body());
        }

        $json = $response->json() ?? [];
        $rows = [];

        // Zoho shapes vary: response.result[] or response[] with field arrays
        $result = $json['response']['result'] ?? $json['data'] ?? $json['result'] ?? [];
        if (! is_array($result)) {
            return [];
        }

        foreach ($result as $item) {
            if (! is_array($item)) {
                continue;
            }
            // Sometimes each item is [ {Field: value}, ... ]
            if (array_is_list($item) && isset($item[0]) && is_array($item[0])) {
                $merged = [];
                foreach ($item as $chunk) {
                    if (is_array($chunk)) {
                        $merged = array_merge($merged, $chunk);
                    }
                }
                $rows[] = $merged;
            } else {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    public function samplePayload(): array
    {
        return [
            'employees' => [[
                'EmployeeID' => 'ZP1001',
                'FirstName' => 'Neha',
                'LastName' => 'Kapoor',
                'EmailID' => 'neha.kapoor@example.com',
                'Mobile' => '9876501234',
                'Department' => 'Sales',
                'Designation' => 'Executive',
                'Dateofjoining' => '2024-04-01',
            ]],
        ];
    }

    public function transformRecord(array $record, array $fieldMap): array
    {
        $out = parent::transformRecord($record, $fieldMap);

        if (isset($out['status_label'])) {
            $label = strtolower((string) $out['status_label']);
            $out['status'] = in_array($label, ['active', '1', 'yes'], true) ? 1 : 0;
            unset($out['status_label']);
        }

        return $out;
    }
}
