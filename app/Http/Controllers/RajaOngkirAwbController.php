<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShippingService;

class RajaOngkirAwbController extends Controller
{
    protected ShippingService $shipping;

    public function __construct(ShippingService $shipping)
    {
        $this->shipping = $shipping;
    }

    public function index(Request $request)
    {
        // Ambil data dari request
        $payload = [
            'awb'               => $request->input('waybill'),
            'courier'           => $request->input('courier'),
            'last_phone_number' => $request->input('last_phone_number'),
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
        ];

        $result = $this->shipping->getWaybill($payload);

        return response()->json($result);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
