<div class="bg-white rounded-lg shadow-md overflow-hidden">
  <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
    <h3 class="text-lg font-semibold text-gray-800">Tambah Layanan Baru</h3>
    <a href="/admin/services" class="text-sm text-blue-600 hover:text-blue-800">&larr; Kembali ke daftar</a>
  </div>

  <div class="p-6">
    <?php if (!empty($errors)): ?>
      <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded">
        <ul class="list-disc ml-5">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="/admin/services/create" method="post">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="service_code" class="block text-sm font-medium text-gray-700">Kode Layanan (slug)</label>
          <input type="text" id="service_code" name="service_code" value="<?= htmlspecialchars($old['service_code'] ?? '') ?>" placeholder="misal: company_profile" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-blue-300 focus:outline-none" required>
          <p class="text-xs text-gray-500 mt-1">Gunakan huruf kecil, angka, strip (-) atau underscore (_)</p>
        </div>

        <div>
          <label for="service_name" class="block text-sm font-medium text-gray-700">Nama Layanan</label>
          <input type="text" id="service_name" name="service_name" value="<?= htmlspecialchars($old['service_name'] ?? '') ?>" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-blue-300 focus:outline-none" required>
        </div>
      </div>

      <div class="mt-6">
        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
        <textarea id="description" name="description" rows="4" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-blue-300 focus:outline-none" placeholder="Deskripsi singkat layanan" required><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
      </div>

      <div class="mt-6">
        <label for="features" class="block text-sm font-medium text-gray-700">Fitur</label>
        <textarea id="features" name="features" rows="5" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-blue-300 focus:outline-none" placeholder="Tulis satu fitur per baris" required><?= htmlspecialchars($old['features'] ?? '') ?></textarea>
        <p class="text-xs text-gray-500 mt-1">Contoh: \n Desain profesional \n Formulir kontak \n Portofolio proyek</p>
      </div>

      <div class="mt-6">
        <label for="price" class="block text-sm font-medium text-gray-700">Harga Mulai (Rp)</label>
        <input type="number" id="price" name="price" value="<?= htmlspecialchars($old['price'] ?? '') ?>" min="1" step="1" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-blue-300 focus:outline-none" required>
      </div>

      <div class="mt-6 flex items-center justify-between">
        <button type="submit" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded shadow focus:outline-none">
          <i class="fas fa-save mr-2"></i> Simpan Layanan
        </button>
        <a href="/admin/services" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
      </div>
    </form>
  </div>
</div>