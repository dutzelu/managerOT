(function () {
  function formatNumber(value, decimals) {
    return new Intl.NumberFormat('ro-RO', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(Number(value || 0));
  }

  function setProgressBar(el, percent) {
    if (!el) return;
    const safePercent = Math.max(0, Math.min(100, Number(percent || 0)));
    el.style.width = safePercent + '%';
  }

  function renderRecent(listEl, recent) {
    if (!listEl) return;
    if (!Array.isArray(recent) || recent.length === 0) {
      listEl.innerHTML = '<li class="cjp-empty">Încă nu există donații înregistrate.</li>';
      return;
    }

    const html = recent.map(function (item) {
      const donor = (item.donor_name || 'Donator anonim').toString();
      const amount = formatNumber(item.amount || 0, 0);
      const rawCurrency = (item.currency || 'RON').toString().toUpperCase();
      const currency = rawCurrency === 'EUR' ? '€' : rawCurrency;
      const rawDate = (item.created_at || '').toString().replace(' ', 'T');
      const date = rawDate ? new Date(rawDate) : new Date();
      const dateLabel = isNaN(date.getTime()) ? '' : date.toLocaleDateString('ro-RO');

      return '<li class="cjp-recent-item">'
        + '<div class="cjp-recent-donor"><strong>' + donor + '</strong><span class="cjp-recent-date">' + dateLabel + '</span></div>'
        + '<strong class="cjp-recent-amount">' + amount + ' ' + currency + '</strong>'
        + '</li>';
    }).join('');

    listEl.innerHTML = html;
  }

  function updateFromStats(stats) {
    if (!stats || typeof stats !== 'object') return;

    const totals = stats.totals || {};
    const materials = stats.materials || {};

    const eurText = document.getElementById('cjp-eur-progress-text');
    const eurBar = document.getElementById('cjp-eur-progress-bar');
    const eurPill = document.getElementById('cjp-eur-progress-pill');
    if (eurText) {
      eurText.textContent = formatNumber(totals.eur_raised || 0, 0)
        + ' € / '
        + formatNumber(totals.eur_goal || 0, 0)
        + ' €';
    }
    if (eurPill) {
      eurPill.textContent = formatNumber(totals.percent || 0, 1) + '%';
    }
    setProgressBar(eurBar, totals.percent || 0);

    Object.keys(materials).forEach(function (key) {
      const item = materials[key] || {};
      const card = document.querySelector('.cjp-material-card[data-key="' + key + '"]');
      if (!card) return;

      const current = card.querySelector('.cjp-current');
      const target = card.querySelector('.cjp-target');
      const unit = card.querySelector('.cjp-unit');
      const bar = card.querySelector('.cjp-material-progress');

      if (current) current.textContent = formatNumber(item.current || 0, 0);
      if (target) target.textContent = formatNumber(item.target || 0, 0);
      if (unit) unit.textContent = (item.unit || '').toString();
      setProgressBar(bar, item.percent || 0);
    });

    const list = document.getElementById('cjp-recent-list');
    renderRecent(list, stats.recent_donations || []);
  }

  async function fetchStats() {
    if (!window.cjpDonationsData || !window.cjpDonationsData.statsEndpoint) {
      return;
    }

    try {
      const res = await fetch(window.cjpDonationsData.statsEndpoint, { credentials: 'same-origin' });
      if (!res.ok) return;
      const stats = await res.json();
      updateFromStats(stats);
    } catch (error) {
      console.error('CJP donations polling failed:', error);
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    fetchStats();
    const poll = Number(window.cjpDonationsData && window.cjpDonationsData.pollInterval) || 30000;
    window.setInterval(fetchStats, poll);

    document.querySelectorAll('.cjp-copy-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const text = btn.getAttribute('data-copy') || '';
        navigator.clipboard.writeText(text).then(function () {
          btn.classList.add('cjp-copy-ok');
          setTimeout(function () { btn.classList.remove('cjp-copy-ok'); }, 1500);
        });
      });
    });
  });
})();
