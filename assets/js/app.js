document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });
});

// Global Quick Search
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('globalSearch');
  const box = document.getElementById('globalSearchSuggest');
  if (!input || !box) return;

  const endpoint = input.closest('.global-search-wrap').dataset.endpoint;
  let timer = null;
  let controller = null;

  function hideResults() {
    box.style.display = 'none';
    box.innerHTML = '';
  }

  function renderResults(items) {
    if (!items.length) {
      box.innerHTML = '<div class="search-empty">No matching records found.</div>';
      box.style.display = 'block';
      return;
    }

    box.innerHTML = items.map((item) => {
      const label = String(item.label || '').replace(/[&<>\"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[c]));
      const meta = String(item.meta || '').replace(/[&<>\"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[c]));
      const type = String(item.type || '').replace(/[&<>\"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[c]));
      return '<a class="global-search-result" href="' + item.url + '">' +
             '<span class="search-result-type">' + type + '</span>' +
             '<strong>' + label + '</strong>' +
             '<small>' + meta + '</small>' +
             '</a>';
    }).join('');
    box.style.display = 'block';
  }

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { hideResults(); return; }

    timer = setTimeout(async () => {
      if (controller) controller.abort();
      controller = new AbortController();
      try {
        const response = await fetch(endpoint + '?q=' + encodeURIComponent(q), {
          headers: { 'Accept': 'application/json' },
          signal: controller.signal
        });
        if (!response.ok) throw new Error('Search request failed');
        const data = await response.json();
        renderResults(Array.isArray(data) ? data : []);
      } catch (error) {
        if (error.name !== 'AbortError') {
          box.innerHTML = '<div class="search-empty">Search is temporarily unavailable.</div>';
          box.style.display = 'block';
        }
      }
    }, 180);
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      hideResults();
      input.blur();
    }
    if (e.key === 'Enter') {
      const first = box.querySelector('a');
      if (first && box.style.display !== 'none') first.click();
    }
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.global-search-wrap')) hideResults();
  });
});
