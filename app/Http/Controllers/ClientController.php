<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $clients = Client::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('clients.index', compact('clients', 'search'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:persona,empresa'],
            'name' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'in:revendedor,cliente_final'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'type.required' => 'El tipo de cliente es obligatorio.',
            'type.in' => 'El tipo de cliente no es válido.',
            'name.required' => 'El nombre o razón social es obligatorio.',
            'model.required' => 'El modelo de cliente es obligatorio.',
            'model.in' => 'El modelo de cliente no es válido.',
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('status', '¡Cliente creado exitosamente!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:persona,empresa'],
            'name' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'in:revendedor,cliente_final'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'type.required' => 'El tipo de cliente es obligatorio.',
            'type.in' => 'El tipo de cliente no es válido.',
            'name.required' => 'El nombre o razón social es obligatorio.',
            'model.required' => 'El modelo de cliente es obligatorio.',
            'model.in' => 'El modelo de cliente no es válido.',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('status', '¡Cliente actualizado correctamente!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('status', '¡Cliente eliminado con éxito!');
    }
}
