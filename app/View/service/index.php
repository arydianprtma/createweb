<section class="py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-4xl font-bold mb-8">Layanan <span class="text-brand-yellow">Kami</span></h1>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
      <?php
        if (!function_exists('format_rupiah')) {
          function format_rupiah($amount) {
            return 'Rp ' . number_format((float)$amount, 0, ',', '.');
          }
        }

        $hasServices = isset($services) && is_array($services) && count($services) > 0;
      ?>

      <?php if ($hasServices): ?>
        <?php foreach ($services as $svc): ?>
          <?php if (!isset($svc['is_active']) || (int)$svc['is_active'] !== 1) continue; ?>
          <?php
            $features = [];
            if (isset($svc['features']) && $svc['features'] !== null && $svc['features'] !== '') {
              $decoded = json_decode($svc['features'], true);
              if (is_array($decoded)) {
                // Flatten fitur dan pecah jika ada literal "\n" yang tersimpan sebagai teks
                $flat = [];
                foreach ($decoded as $fi) {
                  if (!is_string($fi)) continue;
                  $fi = trim($fi);
                  if ($fi === '') continue;
                  // Jika ada literal backslash-n, pecah menjadi beberapa item fitur
                  if (strpos($fi, "\\n") !== false) {
                    $parts = explode("\\n", $fi);
                    foreach ($parts as $p) {
                      $p = trim($p);
                      if ($p !== '') $flat[] = $p;
                    }
                  } else {
                    $flat[] = $fi;
                  }
                }
                $features = $flat;
              }
            }
          ?>
          <div class="p-6 rounded-xl bg-white/5 border border-white/10 hover:border-brand-yellow transition">
            <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($svc['service_name'] ?? 'Layanan') ?></h3>
            <p class="text-white/70 mb-4"><?= htmlspecialchars($svc['description'] ?? '') ?></p>
            <?php if (!empty($features)): ?>
              <ul class="space-y-2 text-white/70">
                <?php foreach ($features as $f): ?>
                  <li class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand-yellow mr-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <?= htmlspecialchars($f) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <div class="mt-6 pt-4 border-t border-white/10">
              <div class="flex justify-between items-center">
                <span class="text-white/70">Mulai dari</span>
                <span class="text-2xl font-bold text-brand-yellow" data-price="<?= htmlspecialchars($svc['service_code'] ?? '') ?>"><?= format_rupiah($svc['price'] ?? 0) ?></span>
              </div>
              <a href="/order?service_code=<?= htmlspecialchars($svc['service_code'] ?? '') ?>" class="mt-4 block text-center bg-brand-yellow hover:bg-yellow-500 text-black font-medium py-2 px-4 rounded-lg transition-all duration-300">
                Pesan Sekarang
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-span-full">
          <div class="p-6 rounded-xl bg-white/5 border border-white/10 text-center">
            <h3 class="text-xl font-semibold mb-2">Belum ada layanan yang aktif</h3>
            <p class="text-white/70 mb-4">Silakan tambah layanan baru melalui halaman admin.</p>
            <a href="/admin/services/new" class="inline-block px-4 py-2 bg-brand-yellow text-black font-medium rounded-lg hover:bg-yellow-500 transition-all">Tambah Layanan</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Cara Kerja CreateWeb -->
    <div class="mb-16">
      <h2 class="text-3xl font-bold mb-8">Cara Kerja <span class="text-brand-yellow">CreateWeb</span></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
        <div class="bg-white/5 border border-white/10 rounded-xl p-6 relative hover:border-brand-yellow transition-all duration-300 transform hover:-translate-y-2">
          <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 w-10 h-10 rounded-full bg-brand-yellow flex items-center justify-center text-black font-bold text-xl">1</div>
          <h3 class="text-xl font-semibold mt-4 mb-3 text-center">Konsultasi</h3>
          <p class="text-white/70 text-center">Diskusi kebutuhan dan tujuan website Anda bersama tim ahli kami.</p>
        </div>
        
        <div class="bg-white/5 border border-white/10 rounded-xl p-6 relative hover:border-brand-yellow transition-all duration-300 transform hover:-translate-y-2">
          <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 w-10 h-10 rounded-full bg-brand-yellow flex items-center justify-center text-black font-bold text-xl">2</div>
          <h3 class="text-xl font-semibold mt-4 mb-3 text-center">Perencanaan</h3>
          <p class="text-white/70 text-center">Pembuatan wireframe, desain, dan perencanaan fitur sesuai kebutuhan.</p>
        </div>
        
        <div class="bg-white/5 border border-white/10 rounded-xl p-6 relative hover:border-brand-yellow transition-all duration-300 transform hover:-translate-y-2">
          <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 w-10 h-10 rounded-full bg-brand-yellow flex items-center justify-center text-black font-bold text-xl">3</div>
          <h3 class="text-xl font-semibold mt-4 mb-3 text-center">Pengembangan</h3>
          <p class="text-white/70 text-center">Pembuatan website dengan teknologi terkini dan standar industri.</p>
        </div>
        
        <div class="bg-white/5 border border-white/10 rounded-xl p-6 relative hover:border-brand-yellow transition-all duration-300 transform hover:-translate-y-2">
          <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 w-10 h-10 rounded-full bg-brand-yellow flex items-center justify-center text-black font-bold text-xl">4</div>
          <h3 class="text-xl font-semibold mt-4 mb-3 text-center">Peluncuran</h3>
          <p class="text-white/70 text-center">Pengujian menyeluruh dan peluncuran website ke server produksi.</p>
        </div>
      </div>
    </div>

    <div class="bg-white/5 border border-white/10 rounded-xl p-8 text-center">
      <h2 class="text-2xl font-semibold mb-4">Butuh Layanan Kustom?</h2>
      <p class="text-white/70 mb-6 max-w-2xl mx-auto">Kami siap membantu kebutuhan spesifik bisnis Anda. Hubungi tim kami untuk konsultasi gratis dan penawaran khusus.</p>
      <a href="/kontak" class="inline-block px-6 py-3 bg-brand-yellow text-black font-medium rounded-lg hover:bg-opacity-90 transition">Hubungi Kami</a>
    </div>
  </div>
</section>