<?php
$title = 'Manage Employee';
?>

<div class="p-4">
  <?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 p-3 rounded bg-green-50 text-green-700 text-sm flex items-center gap-2">
      <i class="fas fa-check-circle"></i>
      <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
    </div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm flex items-center gap-2">
      <i class="fas fa-exclamation-circle"></i>
      <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="px-6 py-5 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
      <h2 class="text-xl font-bold text-gray-900">Manajemen Karyawan</h2>
      <p class="text-sm text-gray-600">Tambah dan kelola akun karyawan admin</p>
    </div>

    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Form Tambah Karyawan -->
      <div class="p-5 bg-slate-50 rounded-xl border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tambah Karyawan Baru</h3>
        <form action="/admin/account/employees/create" method="post" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="full_name" minlength="3" required class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none" placeholder="Contoh: John Doe">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Username (Login)</label>
            <input type="text" name="username" pattern="[A-Za-z0-9]+" required class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none" placeholder="Hanya huruf dan angka">
            <p class="text-xs text-gray-500 mt-1">Hanya alphanumeric (A-Z, a-z, 0-9)</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role" required class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
              <option value="" disabled selected>Pilih role</option>
              <option value="Admin">Admin</option>
              <option value="Superadmin" <?= (!empty($can_create_superadmin) && $can_create_superadmin) ? '' : 'disabled' ?>>Superadmin</option>
            </select>
            <?php if (empty($can_create_superadmin) || !$can_create_superadmin): ?>
              <p class="text-xs text-gray-500 mt-1">Hanya Superadmin yang dapat membuat akun Superadmin</p>
            <?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Password (Login)</label>
            <input type="password" name="password" required class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none" placeholder="Minimal 8 karakter, kombinasi huruf besar, kecil, angka">
            <p class="text-xs text-gray-500 mt-1">Wajib: huruf besar, huruf kecil, dan angka</p>
          </div>
          <div class="flex items-center justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
              <i class="fas fa-save mr-2"></i>
              Simpan
            </button>
          </div>
        </form>
      </div>

      <!-- Daftar Karyawan -->
      <div class="p-5 bg-white rounded-xl border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar Karyawan</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left text-gray-600">
                <th class="py-2 px-3 border-b">Nama</th>
                <th class="py-2 px-3 border-b">Username</th>
                <th class="py-2 px-3 border-b">Role</th>
                <th class="py-2 px-3 border-b">Status</th>
                <th class="py-2 px-3 border-b">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($employees)): ?>
                <?php foreach ($employees as $emp): ?>
                  <tr>
                    <td class="py-2 px-3 border-b text-gray-800"><?= htmlspecialchars($emp['full_name'] ?? '') ?></td>
                    <td class="py-2 px-3 border-b text-gray-800"><?= htmlspecialchars($emp['username'] ?? '') ?></td>
                    <td class="py-2 px-3 border-b text-gray-800"><?= htmlspecialchars($emp['role'] ?? 'Admin') ?></td>
                    <td class="py-2 px-3 border-b text-gray-800">
                      <?php $active = isset($emp['is_active']) ? (int)$emp['is_active'] === 1 : 1; ?>
                      <span class="inline-flex items-center gap-2 <?= $active ? 'text-green-700' : 'text-gray-600' ?>">
                        <i class="fas <?= $active ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                        <?= $active ? 'Aktif' : 'Non-aktif' ?>
                      </span>
                    </td>
                    <td class="py-2 px-3 border-b text-gray-800">
                      <div class="flex items-center gap-2">
                        <a href="/admin/account/employees/edit/<?= (int)($emp['id'] ?? 0) ?>" class="px-2 py-1 text-xs rounded bg-indigo-600 text-white hover:bg-indigo-700"><i class="fas fa-edit mr-1"></i>Edit</a>
                        <form action="/admin/account/employees/delete/<?= (int)($emp['id'] ?? 0) ?>" method="post" onsubmit="return confirm('Hapus karyawan ini?');" class="inline">
                          <button type="submit" class="px-2 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i>Delete</button>
                        </form>
                        <?php if ($active): ?>
                          <form action="/admin/account/employees/deactivate/<?= (int)($emp['id'] ?? 0) ?>" method="post" class="inline">
                            <button type="submit" class="px-2 py-1 text-xs rounded bg-gray-600 text-white hover:bg-gray-700"><i class="fas fa-user-slash mr-1"></i>Nonaktifkan</button>
                          </form>
                        <?php else: ?>
                          <form action="/admin/account/employees/activate/<?= (int)($emp['id'] ?? 0) ?>" method="post" class="inline">
                            <button type="submit" class="px-2 py-1 text-xs rounded bg-green-600 text-white hover:bg-green-700"><i class="fas fa-user-check mr-1"></i>Aktifkan</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="py-4 px-3 text-center text-gray-500">Belum ada karyawan terdaftar</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>