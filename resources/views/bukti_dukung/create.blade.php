<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tambah Bukti Dukung') }} - {{ $proyek->nama_proyek }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('bukti-dukung.store', $proyek->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <p class="mb-2">Petunjuk Upload:</p>
                            <ul class="list-disc list-inside text-sm mb-4">
                                <li>Ukuran file maksimal 10MB per file</li>
                                <li>Dapat memilih beberapa file sekaligus</li>
                                <li>Format file yang diizinkan: Gambar (jpg, jpeg, png, gif) dan Dokumen (pdf, doc, docx, xls, xlsx, ppt, pptx, txt)</li>
                                <li>File akan disimpan di Google Drive</li>
                            </ul>

                            <label for="files" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pilih File (dapat memilih beberapa file sekaligus)</label>
                            <input type="file" name="files[]" id="files" multiple class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" required>

                            <!-- Preview files yang dipilih -->
                            <div id="file-preview" class="mt-3 hidden">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File yang dipilih:</p>
                                <div id="file-list" class="space-y-1"></div>
                            </div>

                            @error('files')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @error('files.*')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="keterangan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Keterangan (opsional)</label>
                            <textarea id="keterangan" name="keterangan" rows="3" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">{{ old('keterangan') }}</textarea>

                            @error('keterangan')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-between">
                            <a href="{{ route('bukti-dukung.index', $proyek->id) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-700 transition">Batal</a>

                            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('files').addEventListener('change', function(e) {
            const files = e.target.files;
            const preview = document.getElementById('file-preview');
            const fileList = document.getElementById('file-list');

            if (files.length > 0) {
                preview.classList.remove('hidden');
                fileList.innerHTML = '';

                Array.from(files).forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between p-2 bg-gray-100 dark:bg-gray-700 rounded text-sm';
                    fileItem.innerHTML = `
                <span class="truncate">${file.name}</span>
                <span class="text-xs text-gray-500 ml-2">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
            `;
                    fileList.appendChild(fileItem);
                });
            } else {
                preview.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>