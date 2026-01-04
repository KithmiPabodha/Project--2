<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class NoticeController extends Controller
{
    public function store(Request $request)
    {
        // Use token-authenticated user info injected by TokenAuthentication middleware
        $staffId = $request->get('user_id');
        $userType = $request->get('user_type');

        if ($userType !== 'staff' || !$staffId) {
            return response()->json(['error' => 'Unauthorized - staff access required'], 401);
        }

        // Validate request
        $request->validate([
            'noticeTitle' => 'required|string|max:255',
            'description' => 'required|string',
            'departments' => 'required|array|min:1',
            'batches' => 'required|array|min:1',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif'
        ]);

        try {
            DB::beginTransaction();

            // Handle attachment
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('public/notices');
            }

            // Insert notice
            $noticeId = DB::table('notice')->insertGetId([
                'title' => $request->noticeTitle,
                'description' => $request->description,
                'date' => now(),
                'staffId' => $staffId,
                'attachment' => $attachmentPath
            ]);

            // Insert notice_departments
            foreach ($request->departments as $deptName) {
                $department = DB::table('departments')->where('departmentName', $deptName)->first();
                if ($department) {
                    DB::table('notice_departments')->insert([
                        'noticeId' => $noticeId,
                        'departmentId' => $department->departmentId
                    ]);
                }
            }

            // Insert notice_batches
            foreach ($request->batches as $batchName) {
                // Map form batch name to DB batch name
                $dbBatchName = match ($batchName) {
                    '2021/22' => '2021/2022',
                    '2022/23' => '2022/2023',
                    '2023/24' => '2023/2024',
                    default => $batchName
                };

                $batch = DB::table('batch')->where('batch', $dbBatchName)->first();

                if (!$batch) {
                    $batchId = DB::table('batch')->insertGetId(['batch' => $dbBatchName]);
                } else {
                    $batchId = $batch->batchId;
                }

                DB::table('notice_batches')->insert([
                    'noticeId' => $noticeId,
                    'batchId' => $batchId
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Notice added successfully!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
