<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'with_services' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Courier::query();

        if ($request->boolean('with_services', true)) {
            $query->with('courier_services');
        }

        if (isset($validated['code'])) {
            $query->where('code', $validated['code']);
        }

        if (isset($validated['search'])) {
            $query->where(function ($query) use ($validated) {
                $query->where('name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('code', 'like', '%' . $validated['search'] . '%');
            });
        }

        $couriers = $query
            ->orderBy('name', $validated['sort'] ?? 'asc')
            ->paginate($validated['per_page'] ?? 25);

        $couriers->withPath('/courier');

        return response()->json($couriers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $courier = Courier::create($validated);

        return response()->json([
            'status' => [
                'code' => 201,
                'description' => 'Created',
            ],
            'data' => $courier,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $courier = $this->findCourier($id);

        if (!$courier) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'OK',
            ],
            'data' => $courier->load('courier_services'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $courier = $this->findCourier($id);

        if (!$courier) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate($this->rules(true, $courier->id));

        $courier->update($validated);

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'Updated',
            ],
            'data' => $courier->fresh('courier_services'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $courier = $this->findCourier($id);

        if (!$courier) {
            return $this->notFoundResponse();
        }

        $courier->delete();

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'Deleted',
            ],
        ]);
    }

    private function rules(bool $partial = false, ?int $ignoreId = null): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'code' => [
                $required,
                'string',
                'max:255',
                Rule::unique('couriers', 'code')->ignore($ignoreId),
            ],
            'logo' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function findCourier(string $id): ?Courier
    {
        return Courier::where('id', $id)
            ->orWhere('code', $id)
            ->first();
    }

    private function notFoundResponse()
    {
        return response()->json([
            'status' => [
                'code' => 404,
                'description' => 'Courier not found.',
            ],
        ], 404);
    }
}
