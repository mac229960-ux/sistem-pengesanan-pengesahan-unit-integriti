<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'hingga' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $log = AuditLog::with(['pengguna:id,name', 'aduan:id,no_rujukan'])
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('dari')))
            ->when($request->filled('hingga'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('hingga')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('tindakan'), fn ($q) => $q->where('tindakan', 'like', $request->input('tindakan').'%'))
            ->when($request->filled('no_rujukan'), fn ($q) => $q->whereHas('aduan', fn ($a) => $a->where('no_rujukan', 'like', '%'.$request->input('no_rujukan').'%')))
            ->orderByDesc('id')
            ->paginate(50)->withQueryString();

        $awalan = AuditLog::query()->selectRaw("DISTINCT SUBSTRING_INDEX(tindakan, '.', 1) AS a")->orderBy('a')->pluck('a');

        return view('admin.audit.index', [
            'log' => $log,
            'pengguna' => User::orderBy('name')->get(['id', 'name']),
            'awalan' => $awalan,
        ]);
    }
}
