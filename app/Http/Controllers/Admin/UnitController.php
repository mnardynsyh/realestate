<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\HousingLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::with('location');

        if ($request->filled('location_id')) {
            $query->where('housing_location_id', $request->location_id);
        }

        return view('admin.unit', [
            'units' => $query->latest()->paginate(10),
            'housings' => HousingLocation::all(),
        ]);
    }

    /* =============================
       CREATE UNIT (DENGAN FOTO)
    ==============================*/
    public function store(Request $request)
    {
        $data = $request->validate([
            'housing_location_id' => 'required|exists:housing_locations,id',
            'block_number'        => 'required|string',
            'type'                => 'required|string',
            'price'               => 'required|numeric',
            'land_area'           => 'required|numeric',
            'building_area'       => 'required|numeric',
            'description'         => 'nullable|string',
            'image'               => 'nullable|image|max:10240',
        ]);

        if ($request->file('image')) {
            $data['image'] = $request->file('image')->store('units', 'public');
        }

        $data['status'] = 'available';

        Unit::create($data);

        return back()->with('success', 'Unit berhasil ditambahkan.');
    }

    /* =============================
       UPDATE DATA (TANPA FOTO)
    ==============================*/
    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        $data = $request->validate([
            'housing_location_id' => 'required|exists:housing_locations,id',
            'block_number'        => 'required|string',
            'type'                => 'required|string',
            'price'               => 'required|numeric',
            'land_area'           => 'required|numeric',
            'building_area'       => 'required|numeric',
            'description'         => 'nullable|string',
            'status'              => 'required|in:available,booked,sold',
        ]);

        $unit->update($data);

        return back()->with('success', 'Data unit berhasil diperbarui.');
    }

    /* =============================
       UPDATE FOTO (KHUSUS FOTO)
    ==============================*/
    public function updateImage(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        if ($unit->image && Storage::disk('public')->exists($unit->image)) {
            Storage::disk('public')->delete($unit->image);
        }

        $unit->update([
            'image' => $request->file('image')->store('units', 'public'),
        ]);

        return back()->with('success', 'Foto unit berhasil diperbarui.');
    }

    /* =============================
       DELETE UNIT
    ==============================*/
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);

        // Cek apakah unit ini ada di tabel transactions
        if ($unit->transactions()->exists()) {
            return back()->with('error', 'Gagal hapus! Unit ini memiliki riwayat transaksi. Silakan ubah statusnya menjadi tidak aktif/terjual.');
        }

        // Hapus gambar jika ada
        if ($unit->image && Storage::disk('public')->exists($unit->image)) {
            Storage::disk('public')->delete($unit->image);
        }

        $unit->delete();

        return back()->with('success', 'Unit berhasil dihapus permanen.');
    }
}
