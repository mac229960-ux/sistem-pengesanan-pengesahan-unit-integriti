<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        return view('notifikasi.index', [
            'notifikasi' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function baca(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        return isset($n->data['aduan_id'])
            ? redirect()->route('aduan.show', $n->data['aduan_id'])
            : redirect()->route('notifikasi.index');
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('berjaya', 'Semua notifikasi ditanda sebagai telah dibaca.');
    }
}
