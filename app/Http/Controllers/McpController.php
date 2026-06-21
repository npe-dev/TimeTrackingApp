<?php

namespace App\Http\Controllers;

use App\Services\McpServer;
use Illuminate\Http\Request;
use Throwable;

/**
 * Minimal MCP server over the Streamable-HTTP transport (JSON request/response).
 * Authenticated via Sanctum bearer token (see routes/api.php).
 */
class McpController extends Controller
{
    private const PROTOCOL_VERSION = '2025-06-18';

    public function handle(Request $request, McpServer $server)
    {
        $payload = $request->json()->all();

        if (! is_array($payload) || empty($payload)) {
            return response()->json($this->error(null, -32700, 'Parse error'), 400);
        }

        // Batch (array of requests) vs single request.
        $isBatch = array_is_list($payload);
        $messages = $isBatch ? $payload : [$payload];

        $responses = [];
        foreach ($messages as $message) {
            $response = $this->handleMessage($message, $server, $request);
            if ($response !== null) {
                $responses[] = $response;
            }
        }

        // Notifications only → no body.
        if (empty($responses)) {
            return response()->noContent(202);
        }

        return response()->json($isBatch ? $responses : $responses[0]);
    }

    private function handleMessage(array $msg, McpServer $server, Request $request): ?array
    {
        $id = $msg['id'] ?? null;
        $method = $msg['method'] ?? null;
        $params = $msg['params'] ?? [];

        // Notifications (no id) get no response.
        $isNotification = ! array_key_exists('id', $msg);

        try {
            $result = match ($method) {
                'initialize' => [
                    'protocolVersion' => $params['protocolVersion'] ?? self::PROTOCOL_VERSION,
                    'capabilities' => ['tools' => (object) []],
                    'serverInfo' => ['name' => McpServer::SERVER_NAME, 'version' => McpServer::SERVER_VERSION],
                ],
                'ping' => (object) [],
                'tools/list' => ['tools' => $server->tools()],
                'tools/call' => $this->callTool($server, $params, $request),
                default => null,
            };
        } catch (Throwable $e) {
            return $isNotification ? null : $this->error($id, -32603, $e->getMessage());
        }

        if ($isNotification) {
            return null;
        }

        if ($method === null || ($result === null && ! str_starts_with((string) $method, 'notifications/'))) {
            return $this->error($id, -32601, "Method not found: {$method}");
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /**
     * tools/call: run the tool. Tool failures are returned as a result with
     * isError=true (so the agent sees the message), not a transport-level error.
     */
    private function callTool(McpServer $server, array $params, Request $request): array
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        try {
            $value = $server->call($name, $args, $request->user());

            return [
                'content' => [['type' => 'text', 'text' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]],
                'isError' => false,
            ];
        } catch (Throwable $e) {
            return [
                'content' => [['type' => 'text', 'text' => 'Error: '.$e->getMessage()]],
                'isError' => true,
            ];
        }
    }

    private function error($id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ];
    }
}
