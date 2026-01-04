<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class StaffHomeController extends Controller
{
    public function getNotices(Request $request)
    {
        // Use token-authenticated user info injected by TokenAuthentication middleware
        $staffId = $request->get('user_id');
        $userType = $request->get('user_type');

        if ($userType !== 'staff' || !$staffId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Please login first.'
            ], 403);
        }

        // Fetch notices for this staff
        $notices = DB::table('notice')
            ->where('staffId', $staffId)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($notice) {
                $notice->formattedDate = date('d-M-Y', strtotime($notice->date));

                // Provide public URL for stored attachment and a friendly filename
                $notice->attachmentUrl = $notice->attachment ? Storage::url($notice->attachment) : null;
                $notice->attachmentName = $notice->attachment_name ?? ($notice->attachment ? basename($notice->attachment) : null);

                // Try to get size, but be tolerant if file missing
                try {
                    $notice->attachmentSize = $notice->attachment ? Storage::size($notice->attachment) : null;
                } catch (\Exception $e) {
                    $notice->attachmentSize = null;
                }

                // Attach related department and batch ids for editor
                $notice->departments = DB::table('notice_departments')
                    ->where('noticeId', $notice->noticeId)
                    ->pluck('departmentId')
                    ->toArray();

                $notice->batches = DB::table('notice_batches')
                    ->where('noticeId', $notice->noticeId)
                    ->pluck('batchId')
                    ->toArray();

                return $notice;
            });

        return response()->json([
            'success' => true,
            'notices' => $notices
        ]);
    }

    /**
     * Return departments and batches lists for the edit UI
     */
    public function getDepartmentsBatches(Request $request)
    {
        $userId = $request->get('user_id');
        $userType = $request->get('user_type');

        if ($userType !== 'staff' || !$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $departments = DB::table('departments')->select('departmentId as id', 'departmentName as name')->get();
        $batches = DB::table('batch')->select('batchId as id', 'batch as name')->get();

        return response()->json(['success' => true, 'departments' => $departments, 'batches' => $batches]);
    }

    /**
     * Update a notice (title, description, departments, batches)
     */
    public function updateNotice(Request $request)
    {
        $userId = $request->get('user_id');
        $userType = $request->get('user_type');

        if ($userType !== 'staff' || !$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'noticeId' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'departments' => 'required|array|min:1',
            'batches' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $noticeId = $request->input('noticeId');

        // Check ownership
        $notice = DB::table('notice')->where('noticeId', $noticeId)->where('staffId', $userId)->first();
        if (!$notice) {
            return response()->json(['success' => false, 'message' => 'Notice not found or access denied'], 404);
        }

        // Update main fields
        DB::table('notice')->where('noticeId', $noticeId)->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'date' => now(),
        ]);

        // Update departments
        DB::table('notice_departments')->where('noticeId', $noticeId)->delete();
        foreach ($request->input('departments') as $deptId) {
            DB::table('notice_departments')->insert([
                'noticeId' => $noticeId,
                'departmentId' => $deptId
            ]);
        }

        // Update batches
        DB::table('notice_batches')->where('noticeId', $noticeId)->delete();
        foreach ($request->input('batches') as $batchId) {
            DB::table('notice_batches')->insert([
                'noticeId' => $noticeId,
                'batchId' => $batchId
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Notice updated successfully']);
    }

    public function deleteNotice(Request $request)
    {
        // Use token-authenticated user info
        $staffId = $request->get('user_id');
        $userType = $request->get('user_type');

        if ($userType !== 'staff' || !$staffId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Please login first.'
            ], 403);
        }

        $noticeId = $request->input('noticeId');

        $notice = DB::table('notice')
            ->where('noticeId', $noticeId)
            ->where('staffId', $staffId)
            ->first();

        if (!$notice) {
            return response()->json([
                'success' => false,
                'message' => 'Notice not found.'
            ]);
        }

        // Delete related records
        DB::table('notice_departments')->where('noticeId', $noticeId)->delete();
        DB::table('notice_batches')->where('noticeId', $noticeId)->delete();

        // Delete notice and attachment (use Storage API)
        if ($notice->attachment && Storage::exists($notice->attachment)) {
            Storage::delete($notice->attachment);
        }
        DB::table('notice')->where('noticeId', $noticeId)->where('staffId', $staffId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notice deleted successfully'
        ]);
    }
}
