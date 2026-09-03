<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth\CompanyRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a brand-new tenant (Company) with its first user
     * as Owner. This is the SaaS self-signup entry point.
     *
     * Tenant-creation logic itself now lives in CompanyRegistrationService
     * (Phase 9), shared with the web UI's RegisterCompany Livewire
     * component — this method's own job is only the API-specific parts:
     * validating the request and issuing a Sanctum token in response.
     */
    public function registerCompany(Request $request, CompanyRegistrationService $registrationService)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_code' => ['required', 'string', 'max:50', 'unique:companies,code'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        $user = $registrationService->register($validated);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'company' => $user->company,
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::withoutGlobalScopes()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'login',
        ]);

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles.permissions', 'company'));
    }
}
