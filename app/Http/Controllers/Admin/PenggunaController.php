<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Peranan;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->input('cari'));

        $pengguna = User::query()
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%")
                ->orWhere('no_pekerja', 'like', "%{$cari}%")))
            ->when($request->filled('peranan'), fn ($q) => $q->where('peranan', $request->input('peranan')))
            ->when($request->filled('aktif'), fn ($q) => $q->where('aktif', $request->input('aktif') === '1'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.pengguna.index', compact('pengguna'));
    }

    public function create()
    {
        return view('admin.pengguna.borang', ['pengguna' => new User(['aktif' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->sahkan($request);
        $data['password'] = $request->input('password');

        $pengguna = User::create($data);
        Audit::log('pengguna.cipta', $pengguna, "Cipta pengguna {$pengguna->email}", baru: $pengguna->only(['name', 'email', 'peranan', 'aktif']));

        return redirect()->route('admin.pengguna.index')->with('berjaya', "Pengguna {$pengguna->name} berjaya didaftarkan.");
    }

    public function edit(User $pengguna)
    {
        return view('admin.pengguna.borang', compact('pengguna'));
    }

    public function update(Request $request, User $pengguna)
    {
        $data = $this->sahkan($request, $pengguna);

        // Pentadbir tidak boleh mengunci dirinya sendiri keluar.
        if ($pengguna->is($request->user())) {
            if (! $data['aktif']) {
                return back()->withInput()->with('ralat', 'Anda tidak boleh menyahaktifkan akaun anda sendiri.');
            }
            if ($data['peranan'] !== Peranan::Pentadbir->value) {
                return back()->withInput()->with('ralat', 'Anda tidak boleh membuang peranan Pentadbir daripada akaun anda sendiri.');
            }
        }

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $pengguna->fill($data)->save();
        Audit::perubahan('pengguna.kemaskini', $pengguna, "Kemas kini pengguna {$pengguna->email}");

        return redirect()->route('admin.pengguna.index')->with('berjaya', 'Maklumat pengguna berjaya dikemas kini.');
    }

    public function resetKataLaluan(Request $request, User $pengguna)
    {
        // Kata laluan sementara yang memenuhi syarat kerumitan.
        $sementara = Str::password(14);
        $pengguna->forceFill(['password' => $sementara, 'remember_token' => Str::random(60)])->save();
        Audit::log('pengguna.reset_kata_laluan', $pengguna, "Reset kata laluan {$pengguna->email}");

        return back()
            ->with('berjaya', "Kata laluan {$pengguna->name} telah ditetapkan semula.")
            ->with('kata_laluan_sementara', ['nama' => $pengguna->name, 'kata_laluan' => $sementara]);
    }

    private function sahkan(Request $request, ?User $pengguna = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($pengguna)],
            'no_pekerja' => ['nullable', 'string', 'max:30', Rule::unique('users', 'no_pekerja')->ignore($pengguna)],
            'jawatan' => ['nullable', 'string', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:30'],
            'peranan' => ['required', new Enum(Peranan::class)],
            'password' => [$pengguna ? 'nullable' : 'required', 'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [], ['name' => 'nama', 'email' => 'e-mel', 'password' => 'kata laluan']);

        unset($data['password']);
        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
