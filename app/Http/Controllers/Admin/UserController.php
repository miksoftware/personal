<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of SaaS registered users.
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');

        $query = User::query()
            ->with('linkedClient:id,name')
            ->withCount([
                'clients',
                'bankAccounts',
                'licenses',
                'developments',
                'sales',
            ])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->paginate(15)->withQueryString();

        // High-level KPI metrics
        $stats = [
            'total'     => User::count(),
            'active'    => User::where('is_active', true)->count(),
            'inactive'  => User::where('is_active', false)->count(),
            'new_month' => User::whereMonth('created_at', now()->month)
                               ->whereYear('created_at', now()->year)
                               ->count(),
        ];

        $clients = Client::orderBy('name')->get(['id', 'name', 'model']);

        return view('admin.users.index', compact('users', 'stats', 'search', 'status', 'clients'));
    }

    /**
     * Link or unlink a SaaS user account to a reseller/admin client so they see shared licenses.
     */
    public function linkClient(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->withErrors([
                'error' => 'La cuenta de Superadministrador no necesita vincularse a un cliente.',
            ]);
        }

        $validated = $request->validate([
            'linked_client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where('user_id', Auth::id()),
            ],
        ], [
            'linked_client_id.exists' => 'El cliente seleccionado no es válido.',
        ]);

        $user->linked_client_id = $validated['linked_client_id'] ?: null;
        $user->save();

        if ($user->linked_client_id) {
            $clientName = Client::find($user->linked_client_id)?->name ?? 'cliente seleccionado';
            return back()->with('status', "Usuario {$user->name} vinculado a {$clientName}. Ahora verá sus licencias compartidas.");
        }

        return back()->with('status', "Se desvinculó el cliente compartido del usuario {$user->name}.");
    }

    /**
     * Toggle active/suspended status for a given user.
     */
    public function toggleStatus(User $user)
    {
        // Prevent disabling oneself or the root superadmin
        if ($user->id === auth()->id() || str_contains(strtolower($user->email), 'softwaremik')) {
            return back()->withErrors([
                'error' => 'Por seguridad, no puedes suspender ni cancelar el acceso de la cuenta principal de Superadministrador.',
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $action = $user->is_active ? 'activado' : 'cancelado / suspendido';

        return back()->with('status', "El acceso para el usuario {$user->name} ({$user->email}) ha sido {$action} correctamente.");
    }

    /**
     * Reset the user password to a generic or custom temporary password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $genericDefault = 'MikSoftware2026*';

        if ($request->filled('custom_password')) {
            $request->validate([
                'custom_password' => ['min:8'],
            ], [
                'custom_password.min' => 'La contraseña personalizada debe contener al menos 8 caracteres.',
            ]);
            $newPassword = $request->input('custom_password');
        } else {
            $newPassword = $request->input('generic_password', $genericDefault);
            if (empty($newPassword)) {
                $newPassword = $genericDefault;
            }
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return back()
            ->with('status', "¡Contraseña actualizada con éxito para {$user->name}!")
            ->with('generic_password_assigned', [
                'user_name' => $user->name,
                'email'     => $user->email,
                'password'  => $newPassword,
            ]);
    }
}
