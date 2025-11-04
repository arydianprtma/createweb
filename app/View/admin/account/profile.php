<?php
$title = 'Profil Akun Admin';
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
    <div class="px-6 py-5 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Profil Akun</h2>
        <p class="text-sm text-gray-600">Kelola informasi akun admin Anda</p>
      </div>
    </div>

    <div class="p-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar Profil -->
        <div class="lg:col-span-1">
          <div class="p-5 bg-slate-50 rounded-xl border border-gray-200">
            <div class="flex items-center gap-4">
              <img src="<?= htmlspecialchars($profile['avatar_url'] ?? '/assets/img/default-avatar.png') ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover border">
              <div>
                <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($profile['full_name'] ?? ($user['username'] ?? 'Admin')) ?></h3>
                <p class="text-sm text-gray-600"><i class="fas fa-user mr-1"></i><?= htmlspecialchars($user['username'] ?? '') ?></p>
                <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($user['email'] ?? '') ?></p>
              </div>
            </div>
            <?php if (!empty($profile['website'])): ?>
              <div class="mt-4">
                <a href="<?= htmlspecialchars($profile['website']) ?>" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 inline-flex items-center">
                  <i class="fas fa-link mr-2"></i> Website
                </a>
              </div>
            <?php endif; ?>
            <?php if (!empty($profile['bio'])): ?>
              <p class="mt-4 text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Form Utama -->
        <div class="lg:col-span-2">
          <form action="/admin/account/profile/update" method="post" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Alamat</label>
                <input type="text" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Website</label>
                <input type="url" name="website" value="<?= htmlspecialchars($profile['website'] ?? '') ?>" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Avatar</label>
                <input type="file" name="avatar_file" accept="image/*" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <p class="mt-1 text-xs text-gray-500">Format JPG/PNG/WEBP, maks 10MB. Jika tidak dipilih, avatar tetap.</p>
                <input type="hidden" name="avatar_url" value="<?= htmlspecialchars($profile['avatar_url'] ?? '') ?>">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Bio</label>
                <textarea name="bio" rows="4" class="mt-1 w-full border rounded px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
              </div>
            </div>
            <div class="flex items-center justify-end">
              <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                <i class="fas fa-save mr-2"></i>
                Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>