/* DogeMine - Shared JS */
(function () {
  'use strict';

  /* ---------- Theme toggle ---------- */
  function getCookie(name) {
    return document.cookie.split('; ').find(r => r.startsWith(name + '='))?.split('=')[1];
  }
  function setCookie(name, value, days) {
    const d = new Date(); d.setTime(d.getTime() + days * 86400000);
    document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
  }
  const root = document.documentElement;
  const themeBtn = document.getElementById('themeToggle');
  function applyTheme(t) {
    root.setAttribute('data-bs-theme', t);
    if (themeBtn) {
      themeBtn.innerHTML = t === 'dark'
        ? '<i class="fas fa-sun"></i>'
        : '<i class="fas fa-moon"></i>';
    }
  }
  applyTheme(getCookie('theme') || 'dark');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      setCookie('theme', next, 365);
      applyTheme(next);
    });
  }

  /* ---------- Sidebar mobile toggle ---------- */
  const sideToggle = document.getElementById('sideToggle');
  const sidebar = document.querySelector('.dm-sidebar');
  if (sideToggle && sidebar) {
    sideToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    document.addEventListener('click', (e) => {
      if (window.innerWidth >= 992) return;
      if (!sidebar.contains(e.target) && !sideToggle.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    });
  }

  /* ---------- Public live stats poll ---------- */
  const depFeed = document.getElementById('depositFeed');
  const witFeed = document.getElementById('withdrawFeed');
  const liveHash = document.getElementById('liveHash');

  function fetchStats() {
    if (!depFeed && !witFeed && !liveHash && !document.getElementById('dmFakeFeed')) return;

    fetch((window.SITE_URL || '') + '/ajax/stats.php', { cache: 'no-store' })
      .then(r => r.json())
      .then(j => {
        if (!j.ok) return;
        if (liveHash) liveHash.textContent = (j.totals.hashrate.toLocaleString()) + ' GH/s';
        if (depFeed && j.recent && j.recent.deposits) {
          depFeed.innerHTML = j.recent.deposits.length
            ? j.recent.deposits.map(d => row(d.wallet, '+ ' + d.amount + ' ' + d.currency, d.time)).join('')
            : '<div class="text-secondary small p-3">No recent deposits.</div>';
        }
        if (witFeed && j.recent && j.recent.withdrawals) {
          witFeed.innerHTML = j.recent.withdrawals.length
            ? j.recent.withdrawals.map(w => row(w.wallet, '- ' + w.amount + ' DOGE', w.time)).join('')
            : '<div class="text-secondary small p-3">No recent withdrawals.</div>';
        }
        if (j.fake && document.getElementById('dmFakeFeed')) showFake(j.fake);
      })
      .catch(() => {});
  }
  function row(mask, val, t) {
    return '<div class="dm-feed-row">' +
      '<span class="dm-mask">' + mask + '</span>' +
      '<span class="text-warning fw-bold">' + val + '</span>' +
      '<span class="small text-secondary">' + t + '</span>' +
      '</div>';
  }
  function showFake(f) {
    const el = document.createElement('div');
    el.className = 'dm-fake-pop';
    el.innerHTML =
      '<div><i class="fa-brands fa-bitcoin text-warning"></i> <strong>' + f.user + '</strong> just ' + f.action + '</div>' +
      '<div class="small">' + f.amount + ' DOGE</div>';
    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 500); }, 4500);
  }

  setTimeout(fetchStats, 600);
  setInterval(fetchStats, 15000);

  /* ---------- Copy to clipboard helper ---------- */
  window.dmCopy = function (id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.select(); el.setSelectionRange(0, 99999);
    try {
      if (navigator.clipboard) navigator.clipboard.writeText(el.value);
      else document.execCommand('copy');
    } catch (e) {}
    const old = event && event.target && event.target.innerHTML;
    if (event && event.target) {
      event.target.innerHTML = '<i class="fas fa-check"></i> Copied';
      setTimeout(() => { event.target.innerHTML = old; }, 1400);
    }
  };

  /* expose SITE_URL from a meta tag if provided, else default to relative */
  if (!window.SITE_URL) {
    const meta = document.querySelector('meta[name="site-url"]');
    window.SITE_URL = meta ? meta.content : '';
  }
})();
