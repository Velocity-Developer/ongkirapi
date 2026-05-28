<?php

namespace App\Http\Controllers;

use App\Models\ShippingLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShippingLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'source' => ['nullable', Rule::in(['db', 'api'])],
            'success' => ['nullable', 'boolean'],
            'method' => ['nullable', 'string', 'max:20'],
            'endpoint' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'status_code' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'created_at_start' => ['nullable', 'date'],
            'created_at_end' => ['nullable', 'date'],
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ShippingLog::query();

        if (isset($validated['source'])) {
            $query->where('source', $validated['source']);
        }

        if (isset($validated['success'])) {
            $query->where('success', $validated['success']);
        }

        if (isset($validated['method'])) {
            $query->where('method', strtoupper($validated['method']));
        }

        if (isset($validated['endpoint'])) {
            $query->where('endpoint', 'like', '%' . $validated['endpoint'] . '%');
        }

        if (isset($validated['domain'])) {
            $query->where('domain', 'like', '%' . $validated['domain'] . '%');
        }

        if (isset($validated['search'])) {
            $query->where('user_agent', 'like', '%' . $validated['search'] . '%');
        }

        if (isset($validated['status_code'])) {
            $query->where('status_code', $validated['status_code']);
        }

        if (isset($validated['created_at_start'])) {
            $query->whereDate('created_at', '>=', $validated['created_at_start']);
        } elseif (isset($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['created_at_end'])) {
            $query->whereDate('created_at', '<=', $validated['created_at_end']);
        } elseif (isset($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $logs = $query
            ->orderBy('created_at', $validated['sort'] ?? 'desc')
            ->paginate($validated['per_page'] ?? 25);

        $logs->withPath('/shipping-log');

        return response()->json($logs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $log = ShippingLog::create($validated);

        return response()->json([
            'status' => [
                'code' => 201,
                'description' => 'Created',
            ],
            'data' => $log,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $log = ShippingLog::find($id);

        if (!$log) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'OK',
            ],
            'data' => $log,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $log = ShippingLog::find($id);

        if (!$log) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate($this->rules(true));

        $log->update($validated);

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'Updated',
            ],
            'data' => $log->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $log = ShippingLog::find($id);

        if (!$log) {
            return $this->notFoundResponse();
        }

        $log->delete();

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'Deleted',
            ],
        ]);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'method' => [$required, 'string', 'max:20'],
            'endpoint' => [$required, 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'source' => [$required, Rule::in(['db', 'api'])],
            'status_code' => ['nullable', 'integer'],
            'success' => [$required, 'boolean'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
            'payload' => [$required, 'array'],
            'error_message' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string'],
        ];
    }

    private function notFoundResponse()
    {
        return response()->json([
            'status' => [
                'code' => 404,
                'description' => 'Shipping log not found.',
            ],
        ], 404);
    }
}
