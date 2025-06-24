<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Buat Laporan Harian') }} - {{ $proyek->nama_proyek }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        <a href="{{ route('bukti-dukung.index', $proyek->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                            </svg>
                            {{ __('Kembali') }}
                        </a>
                    </div>

                    <form action="{{ route('laporan-harian.store', $proyek->id) }}" method="POST" id="laporanForm">
                        @csrf
                        
                        <!-- Info Dasar -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="judul_laporan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Judul Laporan</label>
                                <input type="text" name="judul_laporan" id="judul_laporan" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                    value="{{ old('judul_laporan') }}" required>
                                @error('judul_laporan')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tipe Waktu</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="tipe_waktu" value="satu_hari" class="form-radio" {{ old('tipe_waktu', 'satu_hari') == 'satu_hari' ? 'checked' : '' }} onchange="toggleWaktuFields()">
                                        <span class="ml-2">Satu Hari</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="tipe_waktu" value="rentang_hari" class="form-radio" {{ old('tipe_waktu') == 'rentang_hari' ? 'checked' : '' }} onchange="toggleWaktuFields()">
                                        <span class="ml-2">Rentang Hari</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Waktu -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                            <div>
                                <label for="tanggal_mulai" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" id="tanggal_mulai" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                    value="{{ old('tanggal_mulai') }}" required>
                                @error('tanggal_mulai')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="tanggal_selesai_field" style="display: none;">
                                <label for="tanggal_selesai" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" id="tanggal_selesai" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                    value="{{ old('tanggal_selesai') }}">
                                @error('tanggal_selesai')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="jam_mulai_field">
                                <label for="jam_mulai" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jam Mulai</label>
                                <input type="time" name="jam_mulai" id="jam_mulai" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                    value="{{ old('jam_mulai') }}">
                                @error('jam_mulai')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="jam_selesai_field">
                                <label for="jam_selesai" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jam Selesai</label>
                                <input type="time" name="jam_selesai" id="jam_selesai" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                    value="{{ old('jam_selesai') }}">
                                @error('jam_selesai')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Detail Kegiatan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="kegiatan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kegiatan</label>
                                <textarea id="kegiatan" name="kegiatan" rows="4" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                    required>{{ old('kegiatan') }}</textarea>
                                @error('kegiatan')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="capaian" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Capaian</label>
                                <textarea id="capaian" name="capaian" rows="4" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                    required>{{ old('capaian') }}</textarea>
                                @error('capaian')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Dasar Pelaksanaan -->
                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Dasar Pelaksanaan Kegiatan</label>
                            <div id="dasar_pelaksanaan_container">
                                <div class="dasar-item bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-300">Deskripsi</label>
                                            <textarea name="dasar_pelaksanaan[0][deskripsi]" rows="2" 
                                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                                                required>{{ old('dasar_pelaksanaan.0.deskripsi') }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-300">Referensi Bukti Dukung (Opsional)</label>
                                            <select name="dasar_pelaksanaan[0][bukti_dukung_id]" 
                                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">
                                                <option value="">-- Pilih Bukti Dukung --</option>
                                                @foreach($proyek->buktiDukungs as $bukti)
                                                    <option value="{{ $bukti->id }}" {{ old('dasar_pelaksanaan.0.bukti_dukung_id') == $bukti->id ? 'selected' : '' }}>
                                                        {{ $bukti->nama_file }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" class="mt-2 text-red-600 hover:text-red-800" onclick="removeDasarPelaksanaan(this)">Hapus</button>
                                </div>
                            </div>
                            <button type="button" id="add_dasar_pelaksanaan" class="mt-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                + Tambah Dasar Pelaksanaan
                            </button>
                        </div>

                        <!-- Bukti Pelaksanaan -->
                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bukti Pelaksanaan yang Dilampirkan</label>
                            @if($proyek->buktiDukungs->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($proyek->buktiDukungs as $bukti)
                                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                            <label class="flex items-start space-x-3">
                                                <input type="checkbox" name="bukti_dukung_ids[]" value="{{ $bukti->id }}" 
                                                    class="mt-1 form-checkbox" 
                                                    {{ in_array($bukti->id, old('bukti_dukung_ids', [])) ? 'checked' : '' }}>
                                                <div class="flex-1">
                                                    <div class="font-medium text-sm">{{ $bukti->nama_file }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $bukti->keterangan ?: 'Tidak ada keterangan' }}
                                                    </div>
                                                    <div class="text-xs text-gray-400 mt-1">
                                                        {{ $bukti->created_at->format('d M Y, H:i') }}
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">Belum ada bukti dukung untuk proyek ini.</p>
                            @endif
                        </div>

                        <!-- Kendala dan Solusi -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="kendala" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kendala/Permasalahan (Opsional)</label>
                                <textarea id="kendala" name="kendala" rows="3" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">{{ old('kendala') }}</textarea>
                                @error('kendala')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="solusi" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Solusi (Opsional)</label>
                                <textarea id="solusi" name="solusi" rows="3" 
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">{{ old('solusi') }}</textarea>
                                @error('solusi')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div class="mb-6">
                            <label for="catatan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan Tambahan (Opsional)</label>
                            <textarea id="catatan" name="catatan" rows="3" 
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">{{ old('catatan') }}</textarea>
                            @error('catatan')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-between">
                            <a href="{{ route('bukti-dukung.index', $proyek->id) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-700 transition">
                                Batal
                            </a>
                            
                            <button type="submit" class="px-6 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Generate Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let dasarPelaksanaanCounter = 1;

        function toggleWaktuFields() {
            const tipeWaktu = document.querySelector('input[name="tipe_waktu"]:checked').value;
            const tanggalSelesaiField = document.getElementById('tanggal_selesai_field');
            const jamMulaiField = document.getElementById('jam_mulai_field');
            const jamSelesaiField = document.getElementById('jam_selesai_field');

            if (tipeWaktu === 'rentang_hari') {
                tanggalSelesaiField.style.display = 'block';
                jamMulaiField.style.display = 'none';
                jamSelesaiField.style.display = 'none';
                
                // Clear jam fields
                document.getElementById('jam_mulai').value = '';
                document.getElementById('jam_selesai').value = '';
            } else {
                tanggalSelesaiField.style.display = 'none';
                jamMulaiField.style.display = 'block';
                jamSelesaiField.style.display = 'block';
                
                // Clear tanggal selesai
                document.getElementById('tanggal_selesai').value = '';
            }
        }

        document.getElementById('add_dasar_pelaksanaan').addEventListener('click', function() {
            const container = document.getElementById('dasar_pelaksanaan_container');
            const newItem = document.createElement('div');
            newItem.className = 'dasar-item bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4';
            
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm text-gray-700 dark:text-gray-300">Deskripsi</label>
                        <textarea name="dasar_pelaksanaan[${dasarPelaksanaanCounter}][deskripsi]" rows="2" 
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full" 
                            required></textarea>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm text-gray-700 dark:text-gray-300">Referensi Bukti Dukung (Opsional)</label>
                        <select name="dasar_pelaksanaan[${dasarPelaksanaanCounter}][bukti_dukung_id]" 
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">
                            <option value="">-- Pilih Bukti Dukung --</option>
                            @foreach($proyek->buktiDukungs as $bukti)
                                <option value="{{ $bukti->id }}">{{ $bukti->nama_file }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="button" class="mt-2 text-red-600 hover:text-red-800" onclick="removeDasarPelaksanaan(this)">Hapus</button>
            `;
            
            container.appendChild(newItem);
            dasarPelaksanaanCounter++;
        });

        function removeDasarPelaksanaan(button) {
            const container = document.getElementById('dasar_pelaksanaan_container');
            if (container.children.length > 1) {
                button.closest('.dasar-item').remove();
            } else {
                alert('Minimal harus ada satu dasar pelaksanaan.');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleWaktuFields();
        });
    </script>
</x-app-layout>