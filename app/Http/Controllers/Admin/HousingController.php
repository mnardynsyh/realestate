<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HousingLocation; // Pastikan Model Sesuai
use Illuminate\Http\Request;

class HousingController extends Controller
{
    public function index(Request $request)
    {
        $query = HousingLocation::query();
        $housings = $query->latest()->paginate(10);

        return view('admin.housing', compact('housings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        HousingLocation::create($request->all());

        return back()->with('success', 'Lokasi baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $housing = HousingLocation::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $housing->update($request->all());

        return back()->with('success', 'Data lokasi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $housing = HousingLocation::findOrFail($id);

        // 1. Cek Validasi: Apakah lokasi ini memiliki Unit?
        if ($housing->units()->exists()) {
            return back()->with('error', 'Gagal hapus! Lokasi ini masih memiliki unit properti yang terdaftar. Hapus unitnya terlebih dahulu.');
        }

        // 2. Jika aman (tidak punya unit), baru boleh dihapus permanen
        $housing->delete();
        
        return back()->with('success', 'Lokasi berhasil dihapus.');
    }
}