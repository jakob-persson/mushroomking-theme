<x-app-layout>
  <div class="max-w-xl mx-auto py-10 px-6 space-y-4">
    <div class="bg-white rounded-2xl p-6 shadow-sm">
      <h1 class="text-xl font-semibold mb-3">Dev: Test create adventure</h1>

      <button id="btn" class="px-4 py-3 rounded-xl bg-black text-white">
        Post sample adventure
      </button>

      <pre id="out" class="mt-4 text-xs bg-gray-100 p-4 rounded-xl overflow-auto"></pre>
    </div>
  </div>

  <script>
    const btn = document.getElementById('btn');
    const out = document.getElementById('out');

    btn.addEventListener('click', async () => {
      out.textContent = 'Posting...';

      const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

      const types = { "Chanterelles": 1.2, "Boletus": 0.5 };

      const fd = new FormData();
      fd.append('location', 'Gothenburg, Sweden');
      fd.append('start_date', new Date().toISOString().slice(0,10));
      fd.append('adventure_text', 'Test from Laravel dev page');
      fd.append('types', JSON.stringify(types));

      const res = await fetch('/adventures', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
      });

      const text = await res.text();
      out.textContent = `HTTP ${res.status}\n\n${text}`;
    });
  </script>
</x-app-layout>
