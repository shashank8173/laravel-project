<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Services\Connectors\ConnectorManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectorWebhookController extends Controller
{
    public function __construct(private ConnectorManager $manager) {}

    public function webhook(Request $request, string $slug): JsonResponse
    {
        $connector = Connector::query()->where('slug', $slug)->first();
        if (! $connector) {
            return response()->json(['status' => 'error', 'message' => 'Unknown connector.'], 404);
        }

        if (! $connector->enabled) {
            return response()->json(['status' => 'error', 'message' => 'Connector is disabled.'], 403);
        }

        $secret = (string) $connector->webhook_secret;
        $provided = (string) (
            $request->header('X-Connector-Secret')
            ?: $request->header('X-Webhook-Secret')
            ?: $request->query('secret', '')
        );

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid connector webhook secret.'], 401);
        }

        $payload = $request->all();
        if ($payload === [] && $request->getContent() !== '') {
            $decoded = json_decode($request->getContent(), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $result = $this->manager->ingestWebhook($connector, $payload);

        return response()->json([
            'status' => 'success',
            'connector' => $connector->slug,
            'connector_name' => $connector->name,
            'sync' => [
                'status' => $result['log']->status,
                'created' => $result['log']->created_count,
                'updated' => $result['log']->updated_count,
                'failed' => $result['log']->failed_count,
            ],
            'results' => $result['results'],
        ], $result['log']->status === 'error' ? 422 : 200);
    }
}
