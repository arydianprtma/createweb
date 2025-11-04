<?php
// $items, optional $created, $shareUrl, $pageTitle available
?>
<div class="px-4 py-4">
  <h1 class="text-2xl font-semibold text-gray-800 mb-4"><?= htmlspecialchars($pageTitle ?? 'Apa Kata Klien Kami') ?></h1>

  <section class="bg-white shadow-sm rounded-lg p-4 mb-6 border border-gray-100">
    <h2 class="text-lg font-medium text-gray-800 mb-3">Buat Link Share untuk Rating</h2>
    <form method="post" action="/admin/feedback/create" class="flex items-end gap-3">
      <div class="flex flex-col">
        <label for="order_id" class="text-sm text-gray-600">Order ID (opsional)</label>
        <input type="number" name="order_id" id="order_id" placeholder="contoh: 123" class="mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 w-48" />
      </div>
      <button type="submit" class="px-4 py-2 rounded-md bg-yellow-400 hover:bg-yellow-500 text-black font-semibold shadow-sm transition">Buat Link</button>
    </form>
    <?php if (!empty($created) && !empty($shareUrl)): ?>
      <div class="mt-4 p-3 rounded-md bg-blue-50 border border-blue-200 flex items-center justify-between">
        <div>
          <span class="text-sm text-gray-700">Link berhasil dibuat:</span>
          <code class="ml-2 px-2 py-1 bg-white border border-gray-200 rounded text-sm"><?= htmlspecialchars($shareUrl) ?></code>
        </div>
        <button type="button" class="px-3 py-2 rounded-md bg-blue-500 hover:bg-blue-600 text-white text-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($shareUrl) ?>')">Copy</button>
      </div>
    <?php endif; ?>
  </section>

  <section class="bg-white shadow-sm rounded-lg p-4 border border-gray-100">
    <h2 class="text-lg font-medium text-gray-800 mb-3">Daftar Feedback</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">ID</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Order ID</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Token</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Nama Klien</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Rating</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Komentar</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Status</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Dibuat</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">Share URL</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($items as $it): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-3 py-2 text-gray-800"><?= (int)$it['id'] ?></td>
              <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars((string)($it['order_id'] ?? '')) ?></td>
              <td class="px-3 py-2"><code class="px-2 py-1 bg-gray-100 rounded border border-gray-200 text-gray-800"><?= htmlspecialchars($it['token']) ?></code></td>
              <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars((string)($it['client_name'] ?? '')) ?></td>
              <td class="px-3 py-2">
                <?php $r = (int)($it['rating'] ?? 0); ?>
                <?php if ($r > 0): ?>
                  <span class="inline-flex items-center gap-1 text-yellow-600 font-semibold">
                    <?php for ($i=0; $i<$r; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    <?php for ($i=$r; $i<5; $i++): ?><i class="far fa-star text-gray-300"></i><?php endfor; ?>
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-3 py-2 text-gray-700"><?= nl2br(htmlspecialchars((string)($it['comment'] ?? ''))) ?></td>
              <td class="px-3 py-2">
                <?php $status = $it['status'] ?? 'pending'; ?>
                <?php if ($status === 'submitted'): ?>
                  <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700 border border-green-200">Submitted</span>
                <?php else: ?>
                  <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800 border border-yellow-200">Pending</span>
                <?php endif; ?>
              </td>
              <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars($it['created_at']) ?></td>
              <td class="px-3 py-2"><a class="text-blue-600 hover:text-blue-800 hover:underline" href="/feedback/<?= urlencode($it['token']) ?>" target="_blank">/feedback/<?= htmlspecialchars($it['token']) ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>