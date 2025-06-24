<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Detail Laporan Harian') }}
            </h2>
            <div class="flex space-x-2">
                @if($laporan->drive_id)
                    <a href="{{ route('laporan-harian.view', $laporan->id) }}" target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        {{ __('Lihat di Drive') }}
                    </a>
                    
                    <a href="{{ route('laporan-harian.download', $laporan->id) }}"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ __('Download') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        <a href="{{ route('laporan-harian.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                            </svg>
                            {{ __('Kembali ke Daftar Laporan') }}
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    <!-- Header Laporan -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
                        <h3 class="text-xl font-bold mb-4">{{ $laporan->judul_laporan }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Pelapor:</p>
                                <p class="font-medium">{{ $laporan->user->name }}</p>
                                <p class="text-sm text-gray-500">{{ $laporan->user->email }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Dibuat:</p>
                                <p class="font-medium">{{ $laporan->created_at->format('d F Y, H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Proyek -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-6">
                        <h4 class="text-lg font-semibold mb-4">Informasi Proyek</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Tim:</p>
                                <p class="font-medium">{{ $laporan->proyek->tim->nama_tim }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Proyek:</p>
                                <p class="font-medium">{{ $laporan->proyek->nama_proyek }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Waktu Pelaksanaan:</p>
                                <p class="font-medium">{{ $laporan->formatted_waktu }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Kegiatan dan Capaian -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-3">Kegiatan</h4>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $laporan->kegiatan }}</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-3">Capaian</h4>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $laporan->capaian }}</p>
                        </div>
                    </div>

                    <!-- Dasar Pelaksanaan -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Dasar Pelaksanaan Kegiatan</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            @if($laporan->dasarPelaksanaans->count() > 0)
                                <ol class="list-decimal list-inside space-y-2">
                                    @foreach($laporan->dasarPelaksanaans as $dasar)
                                        <li class="text-gray-700 dark:text-gray-300">
                                            {{ $dasar->formatted_deskripsi }}
                                            @if($dasar->buktiDukung)
                                                <span class="text-blue-600 dark:text-blue-400 text-sm ml-2">
                                                    [Ref: {{ $dasar->buktiDukung->nama_file }}]
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada dasar pelaksanaan yang tercatat.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Bukti Pelaksanaan -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Bukti Pelaksanaan</h4>
                        @if($laporan->buktiDukungs->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($laporan->buktiDukungs as $index => $bukti)
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <div class="flex items-start space-x-3">
                                            <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full">{{ $index + 1 }}</span>
                                            <div class="flex-1">
                                                <h5 class="font-medium text-sm">{{ $bukti->nama_file }}</h5>
                                                @if($bukti->keterangan)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $bukti->keterangan }}</p>
                                                @endif
                                                <div class="flex space-x-2 mt-2">
                                                    <a href="{{ route('bukti-dukung.view', $bukti->id) }}" target="_blank" 
                                                        class="text-blue-600 dark:text-blue-400 hover:underline text-xs">
                                                        Lihat
                                                    </a>
                                                    <a href="{{ route('bukti-dukung.download', $bukti->id) }}" 
                                                        class="text-green-600 dark:text-green-400 hover:underline text-xs">
                                                        Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada bukti pelaksanaan yang dilampirkan.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Kendala dan Solusi -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-3">Kendala/Permasalahan</h4>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $laporan->kendala ?: 'Tidak ada kendala yang dilaporkan.' }}</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-3">Solusi</h4>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $laporan->solusi ?: 'Tidak ada solusi yang tercatat.' }}</p>
                        </div>
                    </div>

                    <!-- Catatan -->
                    @if($laporan->catatan)
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold mb-4">Catatan Tambahan</h4>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $laporan->catatan }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- File Information -->
                    @if($laporan->drive_id)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-3">Informasi File</h4>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium">{{ $laporan->file_path }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">File disimpan di Google Drive</p>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="{{ route('laporan-harian.view', $laporan->id) }}" target="_blank"
                                        class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                        Lihat
                                    </a>
                                    <a href="{{ route('laporan-harian.download', $laporan->id) }}"
                                        class="px-3 py-1 bg-purple-600 text-white rounded text-sm hover:bg-purple-700">
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>