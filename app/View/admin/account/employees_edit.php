<?php
$title = 'Edit Karyawan';
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

  <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden max-w-2xl mx-auto">
    <div class="px-6 py-5 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
      <h2 class="text-xl font-bold text-gray-900">Edit Karyawan</h2>
      <p class="text-sm text-gray-600">Perbarui data akun karyawan admin</p>
    </div>

    <div class="p-6">
      <form action="/admin/account/employees/update" method="post" class="space-y-4">
        <input type="hidden" name="id" value="<?= (int)($employee['id'] ?? 0) ?>" />
        <div>
          <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
          <input type="text" name="full_name" minlength="3" required value="<?= htmlspecialchars($employee['full_name'] ?? '') ?>" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Username (Login)</label>
          <input type="text" name="username" pattern="[A-Za-z0-9]+" required value="<?= htmlspecialchars($employee['username'] ?? '') ?>" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
          <p class="text-xs text-gray-500 mt-1">Hanya alphanumeric (A-Z, a-z, 0-9)</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Role</label>
          <select name="role" required class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="Admin" <?= (($employee['role'] ?? '') === 'Admin') ? 'selected' : '' ?>>Admin</option>
            <option value="Superadmin" <?= (($employee['role'] ?? '') === 'Superadmin') ? 'selected' : '' ?> <?= (!empty($can_assign_superadmin) && $can_assign_superadmin) ? '' : 'disabled' ?>>Superadmin</option>
          </select>
          <?php if (empty($can_assign_superadmin) || !$can_assign_superadmin): ?>
            <p class="text-xs text-gray-500 mt-1">Hanya Superadmin yang dapat menetapkan role Superadmin</p>
          <?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Password (Login) — Opsional</label>
          <input type="password" name="password" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none" placeholder="Isi hanya jika ingin mengganti password">
          <p class="text-xs text-gray-500 mt-1">Jika diisi: minimal 8 karakter, mengandung huruf besar, kecil, dan angka</p>
        </div>
        <div class="flex items-center justify-between">
          <a href="/admin/account/employees" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Kembali</a>
          <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
            <i class="fas fa-save mr-2"></i>
            Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>