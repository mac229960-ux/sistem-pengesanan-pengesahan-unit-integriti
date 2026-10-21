{{-- Carian Sistem HR Bersepadu. Memanggil tambah(pegawai) pada komponen Alpine induk. --}}
<div x-data="{ q: '', hasil: [], ralat: '', memuat: false,
        async cari() {
            this.ralat = '';
            if (this.q.length < 2) { this.hasil = []; return; }
            this.memuat = true;
            try {
                const r = await window.axios.get(@js(route('hr.cari')), { params: { q: this.q } });
                this.hasil = r.data;
            } catch (e) { this.ralat = e.response?.data?.ralat ?? 'Carian gagal.'; this.hasil = []; }
            this.memuat = false;
        } }" class="relative">
    <label class="label">Cari pegawai dalam Sistem HR Bersepadu <span class="badge bg-amber-100 text-amber-800">MOCK</span></label>
    <input x-model="q" @input.debounce.400ms="cari()" @keydown.enter.prevent class="input" placeholder="Taip nama, no. pekerja atau no. KP…">
    <p x-show="memuat" class="mt-1 text-xs text-slate-500">Mencari…</p>
    <p x-show="ralat" x-text="ralat" class="error"></p>
    <div x-show="hasil.length" x-cloak @click.outside="hasil=[]" class="absolute z-10 mt-1 max-h-72 w-full overflow-auto rounded-md bg-white shadow-lg ring-1 ring-slate-200">
        <template x-for="p in hasil" :key="p.id">
            <button type="button" @click="tambah(p); hasil=[]; q=''" class="block w-full border-b border-slate-100 px-3 py-2 text-left text-sm hover:bg-mbsp-50">
                <div class="font-medium" x-text="p.nama"></div>
                <div class="text-xs text-slate-500" x-text="`${p.no_pekerja} · ${p.jawatan ?? ''} ${p.gred ? '(' + p.gred + ')' : ''} · ${p.jabatan ?? ''}`"></div>
            </button>
        </template>
    </div>
</div>
