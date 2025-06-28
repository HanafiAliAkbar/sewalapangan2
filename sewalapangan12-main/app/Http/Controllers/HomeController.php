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

    public function sewastore(Request $request)
    {
        $messages = [
            'required' => ': Attribute harus diisi.'
        ];
        $validator = Validator::make($request->all(), [
            'namaPenyewa' => 'required',
            'tanggal' => 'required|date',
            'jamMulai' => 'required',
            'jamSelesai' => 'required'
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // --- LOGIKA PENGECEKAN LIMIT YANG DIPERBAIKI ---

        $lapanganId = $request->lapanganId;
        $tanggalSewa = $request->tanggal;

        // 1. Ambil data lapangan untuk mendapatkan limitnya
        $lapangan = Lapangan::find($lapanganId);
        if (!$lapangan) {
            Alert::error('Gagal!', 'Lapangan tidak ditemukan.');
            return redirect()->back();
        }

        // 2. Hitung jumlah sewa yang statusnya PENDING ('0') atau DITERIMA ('1')
        $jumlahSewaAktif = Sewa::where('lapangan_id', $lapanganId)
                                 ->where('tanggal', $tanggalSewa)
                                 ->whereIn('acc', ['0', '1']) // <-- PERUBAHAN UTAMA DI SINI
                                 ->count();

        // 3. Bandingkan dengan limit
        if ($jumlahSewaAktif >= $lapangan->limit_sewa) {
            // Jika jumlah sewa aktif (pending + diterima) sudah mencapai limit, tolak request baru
            Alert::error('Gagal!', 'Maaf, kuota pemesanan lapangan untuk tanggal yang Anda pilih sudah penuh.');
            return redirect()->back()->withInput();
        }

        // --- LOGIKA PENGECEKAN SELESAI ---
        
        // Jika kuota masih ada, lanjutkan proses penyimpanan
        $sewa = new Sewa;
        $sewa->nama_penyewa = $request->namaPenyewa;
        $sewa->jam_mulai = $request->jamMulai;
        $sewa->jam_selesai = $request->jamSelesai;
        $sewa->tanggal = $request->tanggal;
        $sewa->biayatotal = $request->biayaTotal;
        $sewa->lapangan_id = $request->lapanganId;
        $sewa->acc = $request->acc;
        $sewa->save();

        Alert::success('Berhasil!', 'Request sewa Anda telah dikirim, silakan tunggu konfirmasi dari admin.');
        return redirect()->route('sewalapangan');
    }
}
