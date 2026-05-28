<?php

namespace App\Http\Controllers;

use App\Models\KodePos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KodePosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'error'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = KodePos::query()
            ->with([
                'subdistrict',
                'rajaongkir_sub_district',
            ]);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['q'])) {
            $query->where(function ($query) use ($validated) {
                $query->where('kode_pos', 'like', '%' . $validated['q'] . '%')
                    ->orWhere('note', 'like', '%' . $validated['q'] . '%');
            });
        }

        $kodePos = $query
            ->orderBy('created_at', $validated['sort'] ?? 'desc')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json($kodePos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $kodePos = KodePos::create($validated);

        return response()->json([
            'status' => [
                'code' => 201,
                'description' => 'Created',
            ],
            'data' => $kodePos->load([
                'subdistrict',
                'rajaongkir_sub_district',
            ]),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kodePos = KodePos::with([
            'subdistrict',
            'rajaongkir_sub_district',
        ])->find($id);

        if (!$kodePos) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'OK',
            ],
            'data' => $kodePos,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $kodePos = KodePos::find($id);

        if (!$kodePos) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate($this->rules($kodePos->id));

        $kodePos->update($validated);

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'Updated',
            ],
            'data' => $kodePos->fresh()->load([
                'subdistrict',
                'rajaongkir_sub_district',
            ]),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kodePos = KodePos::find($id);

        if (!$kodePos) {
            return $this->notFoundResponse();
        }

        $kodePos->delete();

        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'Deleted',
            ],
        ]);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'kode_pos' => [
                'required',
                'string',
                'max:10',
                Rule::unique('kode_pos', 'kode_pos')->ignore($ignoreId),
            ],
            'status' => ['required', Rule::in(['active', 'inactive', 'error'])],
            'rajaongkir_sub_districts_id' => ['nullable', 'exists:rajaongkir_sub_districts,id'],
            'subdistricts_id' => ['nullable', 'exists:subdistricts,id'],
            'note' => ['nullable', 'string'],
        ];
    }

    private function notFoundResponse()
    {
        return response()->json([
            'status' => [
                'code' => 404,
                'description' => 'Kode pos not found.',
            ],
        ], 404);
    }
}
