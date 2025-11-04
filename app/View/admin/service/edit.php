<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Edit Harga Layanan</h3>
    </div>
    
    <div class="p-6">
        <form action="/admin/services/update" method="post">
            <input type="hidden" name="service_code" value="<?= $service['service_code'] ?>">
            
            <div class="mb-4">
                <label for="service_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Layanan</label>
                <input type="text" id="service_name" name="service_name" value="<?= $service['service_name'] ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" readonly>
                <p class="text-xs text-gray-500 mt-1">Nama layanan tidak dapat diubah</p>
            </div>
            
            <div class="mb-6">
                <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Harga (Rp)</label>
                <input type="number" id="price" name="price" value="<?= $service['price'] ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300">
                    Simpan Perubahan
                </button>
                <a href="/admin/services" class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800">
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>