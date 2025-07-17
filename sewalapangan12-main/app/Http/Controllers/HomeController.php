<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Lapangan;
use App\Models\Sewa;



class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('index');
    }

    public function sewalapangan()
    {
        $lapangans = Lapangan::all();
        return view ('sewalapangan', ['lapangans' => $lapangans]);
    }

    

    public function formsewa(Request $request)
    {
        $receivedData = $request->input('lapangan_id');
        $lapangan = Lapangan::find($receivedData);

        return view('formsewa', compact('lapangan'));
    }

    public function sewastore(Request $request){
    $messages = [
        'required' => ': Attribute harus diisi.'
    ];
    $validator = Validator::make($request->all(), [
        'namaPenyewa' => 'required',
        'tanggal' => 'required',
        'jamMulai' => 'required',
        'jamSelesai' => 'required'
    ], $messages);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Cek apakah sudah ada sewa di tanggal & jam yang sama (overlap)
    $cekJadwal = Sewa::where('lapangan_id', $request->lapanganId)
        ->where('tanggal', $request->tanggal)
        ->where(function($query) use ($request) {
            $query->whereBetween('jam_mulai', [$request->jamMulai, $request->jamSelesai])
                  ->orWhereBetween('jam_selesai', [$request->jamMulai, $request->jamSelesai])
                  ->orWhere(function($q) use ($request) {
                      $q->where('jam_mulai', '<=', $request->jamMulai)
                        ->where('jam_selesai', '>=', $request->jamSelesai);
                  });
        })
        ->exists();

    if ($cekJadwal) {
        Alert::error('Gagal', 'Maaf, lapangan sudah disewa pada waktu tersebut.');
        return redirect()->back()->withInput();
    }

    // Jika aman, simpan
    $sewa = new Sewa;
    $sewa->nama_penyewa = $request->namaPenyewa;
    $sewa->jam_mulai = $request->jamMulai;
    $sewa->jam_selesai = $request->jamSelesai;
    $sewa->tanggal = $request->tanggal;
    $sewa->biayatotal = str_replace(',', '', $request->biayaTotal);
    $sewa->lapangan_id = $request->lapanganId;
    $sewa->acc = $request->acc;
    $sewa->save();

    Alert::success('Berhasil', 'Pemesanan berhasil dibuat.');
    return redirect()->route('sewalapangan');
}

}
