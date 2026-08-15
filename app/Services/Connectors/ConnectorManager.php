<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\ConnectorSyncLog;
use App\Services\EmployeeUpsertService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ConnectorManager
{
    public function __construct(
        private EmployeeUpsertService $upsert,
        private GenericEmployeeConnector $generic
    ) {}

    public function driver(): GenericEmployeeConnector
    {
        return $this->generic;
    }

    public function ensureDefaults(): void
    {
        if (Connector::query()->exists()) {
            return;
        }

        Connector::query()->create([
            'type' => 'generic',
            'slug' => 'main',
            'name' => 'Main Connector',
            'enabled' => false,
            'field_map' => $this->generic->defaultFieldMap(),
            'webhook_secret' => 'whsec_'.\Illuminate\Support\Str::random(40),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{log:ConnectorSyncLog,results:list<array<string,mixed>>}
     */
    public function ingestWebhook(Connector $connector, array $payload): array
    {
        $map = $connector->field_map ?: $this->generic->defaultFieldMap();
        $records = $this->generic->extractRecords($payload);

        return $this->processRecords($connector, $map, $records, 'inbound');
    }

    /**
     * @param  array<string, mixed>|null  $customPayload
     * @return array{log:ConnectorSyncLog,results:list<array<string,mixed>>}
     */
    public function runTest(Connector $connector, ?array $customPayload = null): array
    {
        $payload = $customPayload ?: $this->generic->samplePayload();

        return $this->ingestWebhook($connector, $payload);
    }

    /**
     * @return array{log:ConnectorSyncLog,results:list<array<string,mixed>>}
     */
    public function pull(Connector $connector): array
    {
        $records = $this->generic->pullRecords($connector->config_array);
        $map = $connector->field_map ?: $this->generic->defaultFieldMap();

        return $this->processRecords($connector, $map, $records, 'pull');
    }

    /**
     * @param  array<string, string>  $map
     * @param  list<array<string, mixed>>  $records
     * @return array{log:ConnectorSyncLog,results:list<array<string,mixed>>}
     */
    private function processRecords(
        Connector $connector,
        array $map,
        array $records,
        string $direction
    ): array {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $results = [];

        foreach ($records as $index => $record) {
            try {
                $payload = $this->generic->transformRecord($record, $map);
                // Keep connector-specific external id namespace only if not already prefixed.
                if (! empty($payload['external_id']) && is_string($payload['external_id'])) {
                    $prefix = $connector->slug.':';
                    if (! str_starts_with($payload['external_id'], $prefix) && ! str_contains($payload['external_id'], ':')) {
                        $payload['external_id'] = $prefix.$payload['external_id'];
                    }
                }

                $result = $this->upsert->upsert($payload);
                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                }
                $results[] = [
                    'index' => $index,
                    'status' => 'ok',
                    'created' => $result['created'],
                    'employee_id' => $result['employee']->id,
                    'office_email' => $result['employee']->office_email,
                ];
            } catch (ValidationException $e) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'status' => 'error',
                    'errors' => $e->errors(),
                ];
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Connector sync row failed', [
                    'connector' => $connector->slug,
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'index' => $index,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $status = $failed === 0 ? 'success' : ($created + $updated > 0 ? 'partial' : 'error');
        $message = sprintf('created=%d updated=%d failed=%d total=%d', $created, $updated, $failed, count($records));

        $log = ConnectorSyncLog::create([
            'connector_id' => $connector->id,
            'direction' => $direction,
            'status' => $status,
            'created_count' => $created,
            'updated_count' => $updated,
            'failed_count' => $failed,
            'details' => ['results' => $results],
        ]);

        $connector->update([
            'last_sync_at' => now(),
            'last_sync_status' => $status,
            'last_sync_message' => $message,
        ]);

        return ['log' => $log, 'results' => $results];
    }
}
