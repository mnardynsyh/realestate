<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Menampilkan daftar customer
     */
    public function index(Request $request)
    {
    
        $query = User::with(['customer', 'transactions'])
                     ->where('role', 'customer');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($subQ) use ($search) {
                      $subQ->where('phone', 'like', "%{$search}%")
                           ->orWhere('nik', 'like', "%{$search}%");
                  });
            });
        }

        $customers = $query->latest()->paginate(10);

        return view('admin.customer', compact('customers'));
    }


    public function destroy($id)
    {
        // Cari user
        $user = User::where('role', 'customer')->findOrFail($id);

        $finishedStatuses = ['sold', 'rejected', 'canceled'];

        // CEK: Apakah ada transaksi yang BELUM selesai?
        $hasOngoingTransactions = $user->transactions()
            ->whereNotIn('status', $finishedStatuses)
            ->exists();

        // JIKA ADA transaksi berjalan -> TOLAK
        if ($hasOngoingTransactions) {
            return back()->with('error', 'Gagal hapus! Customer ini sedang dalam proses transaksi aktif (Booking/Pemberkasan/Bank). Selesaikan atau batalkan transaksi tersebut terlebih dahulu.');
        }
        
        // Hapus detail profile customer
        if ($user->customer) {
            $user->customer->delete();
        }

        $user->delete();

        return back()->with('success', 'Data customer berhasil dihapus (Riwayat transaksi yang sudah selesai ikut terhapus).');
    }
}