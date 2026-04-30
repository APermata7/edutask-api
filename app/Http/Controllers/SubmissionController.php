<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    // 1. READ ALL (Menampilkan Daftar Tugas)
    public function index()
    {
        $user = auth()->user(); // Mengambil data user yang sedang login dari Token

        // Logika Role: Jika Admin atau lecture tampilkan semua, jika Member tampilkan miliknya saja
        // (Asumsi kolom role di tabel users bernama 'role')
        if ($user->role === 'admin' || $user->role === 'lecturer') {
            $submissions = Submission::with('user')->get();  // Mengambil semua submission beserta data user yang mengumpulkan
        } else {
            $submissions = Submission::where('user_id', $user->id)->with('user')->get();
        }

        // Response sesuai template laporan
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar submission',
            'data'    => $submissions
        ], 200);
    }

    // 2. CREATE (Member Mengumpulkan Tugas)
    public function store(Request $request)
    {
        // Validasi inputan dari Postman/Frontend
        $request->validate([
            'task_title' => 'required|string',
            'file_url'   => 'required|string'
        ]);

        // Proses Insert ke tabel submissions
        $submission = Submission::create([
            'user_id'    => auth()->id(), // Otomatis terisi ID user yang sedang login
            'task_title' => $request->task_title,
            'file_url'   => $request->file_url,
            'grade'      => null // Nilai kosong saat baru dikumpul
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dikumpulkan!',
            'data'    => $submission
        ], 201);
    }

    // 3. READ SINGLE (Menampilkan 1 Detail Tugas)
    public function show($id)
    {
        $submission = Submission::find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data submission tidak ditemukan!'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Data Submission!',
            'data'    => $submission
        ], 200);
    }

    // 4. UPDATE (Hanya untuk Member Merevisi Tugas)
    public function update(Request $request, $id)
    {
        $submission = Submission::find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data submission tidak ditemukan!'
            ], 404);
        }

        // KEAMANAN: Pastikan yang mau update adalah user yang sama dengan yang mengumpulkan tugas
        if ($submission->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak merevisi tugas milik orang lain!'
            ], 403); // 403 Forbidden (Dilarang)
        }

        // Update data. Hanya task_title dan file_url yang boleh diubah.
        $submission->update([
            'task_title' => $request->task_title ?? $submission->task_title,
            'file_url'   => $request->file_url ?? $submission->file_url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil direvisi!',
            'data'    => $submission
        ], 200);
    }

    // 5. DELETE (Hapus Tugas)
    public function destroy($id)
    {
        $submission = Submission::find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data submission tidak ditemukan!'
            ], 404);
        }

        $submission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dihapus!'
        ], 200);
    }
}