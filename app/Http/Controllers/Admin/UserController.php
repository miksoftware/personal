<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        return view('admin.users.index', compact('users', 'stats', 'search', 'status'));
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
