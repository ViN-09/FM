<?php

namespace App\Http\Controllers\ttc_teling_controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

Carbon::setLocale('id');

class reciver extends Controller
{
    protected $connection = 'mysql';

    public function receiveRawJson(Request $request)
    {
        $json = $request->getContent();
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => 'error',
                'message' => 'JSON tidak valid',
                'error' => json_last_error_msg()
            ], 400);
        }

        try {
            // Gunakan connection khusus
            \DB::connection($this->connection)
                ->table('cacepue')
                ->insert($data); // insert langsung, key JSON = kolom, value = value

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'inserted_data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function avgDPM(Request $request)
{
    // 1. Daftar kolom numerik secara manual
    $columns = [
        'kw_lv1',
        'kva_lv1',
        'kw_lv2',
        'kva_lv2',
        'total_kva_pln',
        'total_load_pln',
        'kw_rec1_2_ups1',
        'kva_rec1_2_ups1',
        'kw_rec4_ups2',
        'kva_rec4_ups2',
        'kw_rec3_5',
        'kva_rec3_5',
        'kw_rec9_10_11_12',
        'kva_rec9_10_11_12',
        'total_kva_it_telco',
        'total_load_it_telco',
        'pue'
    ];

    if (empty($columns)) {
        return response()->json(['error' => 'No numeric columns found']);
    }

    // 2. Buat query dinamis AVG
    $avgColumns = collect($columns)
        ->map(fn($col) => "AVG(`$col`) AS `$col`")
        ->implode(', ');

    $query = "SELECT $avgColumns FROM cacepue";

    // 3. Jalankan query
    $result = DB::connection($this->connection)->select($query);

    $row = $result[0] ?? null;
    if ($row) {
        $row = (array) $row;   // convert object -> array
        $this->pushDPM($row);
    }

    return response()->json($row);
}

    public function pushDPM($data)
    {
        try {
            // Tambahkan timestamp WITA
            $data['date'] = $this->clock(); // pastikan clock() return string

            // Insert ke tabel data_pue
            DB::connection($this->connection)
                ->table('data_pue')
                ->insert($data);

            // Jika insert berhasil, kosongkan tabel cacepue
            $this->removeCacheData('cacepue');

            return true;
        } catch (\Exception $e) {
            // Tangani error
            \Log::error('pushDPM error: ' . $e->getMessage());
            return false;
        }
    }



    public function clock()
    {
        // Set timezone WITA (UTC+8)
        $now = Carbon::now('Asia/Makassar');

        // Format tanggal & waktu: yyyy-mm-dd hh:mm:ss
        $formatted = $now->format('Y-m-d H:i:s');

        return $formatted;
    }

    public function removeCacheData($tableName)
    {
        try {
            // Validasi supaya user tidak bisa sembarangan menghapus tabel sistem
            $allowedTables = ['cacepue', 'per_minute_table']; // daftar tabel yang diizinkan
            if (!in_array($tableName, $allowedTables)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tabel tidak diizinkan untuk dihapus'
                ], 400);
            }

            DB::connection($this->connection)
                ->table($tableName)
                ->truncate(); // hapus semua data dan reset auto increment

            return response()->json([
                'status' => 'success',
                'message' => "Tabel $tableName berhasil dikosongkan"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }





    public function hello()
    {
        return response()->json(['message' => 'Hello from Reciver!']);
    }

    
    
}
