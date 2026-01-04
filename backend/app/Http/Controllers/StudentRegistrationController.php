<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentRegistrationController extends Controller
{
    public function register(Request $request)
    {
        // Get studentId from request
        $studentId = $request->input('studentId');

        if (!$studentId) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'departmentId' => 'required',
            'batchId' => 'required',
            'password' => 'required|string|min:6',
            'confirm' => 'required|string|same:password',
        ]);

        $name = $request->input('name');
        $email = $request->input('email');
        $departmentId = $request->input('departmentId');
        $batchId = $request->input('batchId');
        $password = $request->input('password');

        // Hash the password
        $hashedPassword = Hash::make($password);

        // Update student record
        $updated = DB::table('students')
            ->where('studentId', $studentId)
            ->update([
                'name' => $name,
                'email' => $email,
                'departmentId' => $departmentId,
                'batchId' => $batchId,
                'password' => $hashedPassword,
                'status' => 'Enrolled'
            ]);

        if ($updated) {
            return response()->json(['message' => 'Registration successful', 'redirect' => '/login']);
        } else {
            return response()->json(['message' => 'Error updating student'], 500);
        }
    }
}
