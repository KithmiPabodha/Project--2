<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffRegistrationController extends Controller
{
    public function register(Request $request)
    {
        // Get staffId from request
        $staffId = $request->input('staffId');

        if (!$staffId) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'confirmPassword' => 'required|string|same:password',
        ]);

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        // Hash password
        $hashedPassword = Hash::make($password);

        // Update staff record
        $updated = DB::table('staff')
            ->where('staffId', $staffId)
            ->update([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'status' => 'Enrolled'
            ]);

        if ($updated) {
            return response()->json(['message' => 'Registration successful', 'redirect' => '/login']);
        } else {
            return response()->json(['message' => 'Error updating staff'], 500);
        }
    }
}

