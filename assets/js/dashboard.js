/* DogeMine - Dashboard live behaviours */
(function () {
  'use strict';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  /* ---------- Live counter (data-counter) ---------- */
  const counters = document.querySelectorAll('[data-counter]');
  counters.forEach((el) => {
    const base = parseFloat(el.dataset.base) || 0;
    const rate = parseFloat(el.dataset.rate) || 0;
    const dec  = parseInt(el.dataset.decimals) || 4;
    const t0   = Date.now();
    function tick() {
      const elapsed = (Date.now() - t0) / 1000;
      const v = base + rate * elapsed;
      el.textContent = v.toFixed(dec);
      requestAnimationFrame(tick);
    }
    tick();
  });

  /* ---------- Mining progress bar animation ---------- */
  const bar = document.getElementById('miningProgress');
  if (bar) {
    let p = 0;
    setInterval(() => {
      p = (p + 6) % 100;
      bar.style.width = p + '%';
    }, 600);
  }

  /* ---------- Server sync every 30s ---------- */
  function syncMining() {
    fetch((window.SITE_URL || '') + '/ajax/mining.php', { cache: 'no-store', credentials: 'same-origin' })
      .then(r => r.json())
      .then(j => {
        if (!j.ok) return;
        // Re-anchor counter to server-truth values so it never drifts.
        counters.forEach((el) => {
          el.dataset.base = j.balance;
          el.dataset.rate = j.per_second;
          // restart animation start time
          el.__t0 = Date.now();
        });
      })
      .catch(() => {});
  }
  setTimeout(syncMining, 5000);
  setInterval(syncMining, 30000);

  /* ---------- Notifications dropdown ---------- */
  const badge = document.getElementById('notifBadge');
  const menu  = document.getElementById('notifMenu');

  function loadNotifs() {
    if (!menu) return;
    fetch((window.SITE_URL || '') + '/ajax/notifications.php', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(j => {
        if (!j.ok) return;
        if (j.unread > 0) { badge.style.display = ''; badge.textContent = j.unread; }
        else { badge.style.display = 'none'; }

        if (!j.items.length) {
          menu.innerHTML = '<div class="px-3 py-3 text-secondary small">No notifications yet.</div>';
          return;
        }
        const html = j.items.map(n =>
          '<div class="dm-notif-item ' + (n.is_read ? '' : 'unread') + '">' +
            '<div><strong>' + escapeHtml(n.title) + '</strong></div>' +
            '<div class="small">' + escapeHtml(n.message) + '</div>' +
            '<div class="small text-end">' + n.time + '</div>' +
          '</div>'
        ).join('');
        menu.innerHTML = html + '<div class="px-3 py-2 text-end"><a href="#" id="markRead" class="text-warning small">Mark all read</a></div>';

        const m = document.getElementById('markRead');
        if (m) m.addEventListener('click', (e) => {
          e.preventDefault();
          const fd = new FormData();
          fd.append('action', 'mark_read');
          fd.append('_csrf', csrf);
          fetch((window.SITE_URL || '') + '/ajax/notifications.php', {
            method: 'POST', body: fd, credentials: 'same-origin'
          }).then(() => loadNotifs());
        });
      })
      .catch(() => {});
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  }
  loadNotifs();
  setInterval(loadNotifs, 60000);

  /* ---------- Public fake feed pop on dashboard ---------- */
  function fakePop() {
    const host = document.getElementById('dmFakeFeed');
    if (!host) return;
    fetch((window.SITE_URL || '') + '/ajax/stats.php', { cache: 'no-store' })
      .then(r => r.json()).then(j => {
        if (j && j.fake) {
          const el = document.createElement('div');
          el.className = 'dm-fake-pop';
          el.innerHTML = '<div><i class="fa-brands fa-bitcoin text-warning"></i> <strong>' + j.fake.user + '</strong> just ' + j.fake.action + '</div>'
                       + '<div class="small">' + j.fake.amount + ' DOGE</div>';
          document.body.appendChild(el);
          requestAnimationFrame(() => el.classList.add('show'));
          setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 500); }, 4500);
        }
      }).catch(() => {});
  }
  setInterval(fakePop, 22000);
})();
