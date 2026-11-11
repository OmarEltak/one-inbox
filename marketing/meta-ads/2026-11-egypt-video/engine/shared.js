/* Scenes shared by both ads. Each factory returns {html, update(el, lt, d, S), sfx?}. */
window.SH = {
  /* Partners, not a template: struck "template" card -> 90-min call -> 4 weekly tunings -> 30 days */
  partner(headline) {
    const steps = [['مكالمة 90 دقيقة', 'نبنيه معاك على منتجاتك وأسلوبك'], ['أسبوع 1 · 2 · 3 · 4', 'نراجع المحادثات ونظبطه'], ['30 يوم', 'معاك على صفحتك خطوة بخطوة']];
    return {
      bg: 'paper',
      html: `<div class="col" style="gap:30px;width:940px">
        <div class="h2" data-h>${headline}</div>
        <div data-t class="row" style="gap:16px;opacity:0;position:relative;background:#fff;border:3px dashed #c9c3b3;border-radius:24px;padding:16px 26px;font-weight:900;font-size:34px;color:#9a9483">
          📦 تمبلت جاهز لكل الناس<i data-x style="position:absolute;left:-10px;right:-10px;top:50%;height:9px;background:var(--red);border-radius:6px;transform-origin:right;transform:scaleX(0)"></i></div>
        <div class="col" style="gap:16px;width:100%">${steps.map((s, i) => `<div data-s="${i}" class="row" style="opacity:0;width:100%;gap:22px;background:#fff;border-radius:26px;padding:20px 26px;box-shadow:0 14px 34px rgba(0,0,0,.08);border:2px solid ${i === 2 ? 'var(--em)' : 'rgba(0,0,0,.06)'}">
          <div style="width:74px;height:74px;border-radius:50%;flex:none;background:${i === 2 ? 'var(--em)' : 'var(--ink)'};color:${i === 2 ? '#fff' : 'var(--yellow)'};display:flex;align-items:center;justify-content:center;font-weight:900;font-size:36px">${i + 1}</div>
          <div><div style="font-weight:900;font-size:40px">${s[0]}</div><div class="muted" style="font-weight:700;font-size:28px">${s[1]}</div></div></div>`).join('')}</div>
      </div>`,
      update(el, lt, d) {
        pop(q(el, '[data-h]'), lt, 0); pop(q(el, '[data-t]'), lt, .35); strike(q(el, '[data-x]'), lt, .9);
        qa(el, '[data-s]').forEach((s, i) => slideX(s, lt, 1.4 + i * (d - 2.2) / 3, .4, -120));
      },
      sfx: d => [[.9, 'tick'], ...[0, 1, 2].map(i => [1.4 + i * (d - 2.2) / 3, 'pop'])],
    };
  },

  /* Pilot card, then the Founding Partner card slides over it. swapAt: fraction of scene, or {line:n} */
  pricing(swapAt, extra) {
    const swap = (d, S) => typeof swapAt === 'object' ? S.rel(swapAt.line) : swapAt * d;
    return {
      bg: 'em',
      html: `<div style="position:relative;width:900px;height:800px">
        <div data-a class="price" style="position:absolute;top:10px;left:0">
          <div class="lbl">AI PILOT · تجربة 14 يوم</div>
          <div class="name">جرّبه الأول</div>
          <div style="margin:8px 0 14px"><span class="amt">3,500</span> <span class="unit">جنيه · مرة واحدة</span></div>
          <div data-g class="row" style="gap:18px;background:#ecfdf5;border:3px solid var(--em);border-radius:24px;padding:16px 22px">
            <svg width="62" height="68" viewBox="0 0 24 26"><path d="M12 1 2 5v7c0 6.5 4.3 11.4 10 13 5.7-1.6 10-6.5 10-13V5z" fill="#00B67A"/><path d="m7 13 3.4 3.4L17 9.8" stroke="#fff" stroke-width="2.6" fill="none" stroke-linecap="round"/></svg>
            <div style="font-weight:900;font-size:34px;line-height:1.3">فلوسك ترجعلك كاملة<br><span style="font-size:26px;color:#4b5563">لو ما اتعاملش مع 50 محادثة حقيقية</span></div>
          </div>
        </div>
        <div data-b class="price" style="position:absolute;top:60px;left:0;opacity:0;border:4px solid var(--yellow)">
          <span class="chip">الأكثر طلباً</span>
          <div class="lbl" style="margin-top:12px">FOUNDING PARTNER</div>
          <div class="name">الشريك المؤسس</div>
          <div><span class="amt">7,500</span> <span class="unit">جنيه إعداد</span></div>
          <div style="font-weight:900;font-size:50px;color:var(--em2)">+ <span class="ltr">2,500</span> جنيه في الشهر</div>
          <div class="col" style="align-items:flex-start;gap:6px;margin-top:10px">
            <div class="check">لحد 3 صفحات على كل المنصات</div>
            <div class="check">مبني مخصوص ليك · 30 يوم معاك</div>
            <div class="check">تلغي في أي وقت</div>
          </div>
          ${extra ? `<div data-x style="opacity:0;margin-top:16px;font-weight:900;font-size:32px;background:var(--ink);color:var(--yellow);border-radius:18px;padding:12px 20px">${extra}</div>` : ''}
        </div></div>`,
      update(el, lt, d, S) {
        const sw = swap(d, S);
        pop(q(el, '[data-a]'), lt, 0); pop(q(el, '[data-g]'), lt, Math.min(1.2, sw * .4), .4);
        const k = eo(p(lt, sw, .5)), a = q(el, '[data-a]');
        a.style.transform = `scale(${1 - .08 * k}) translateY(${-60 * k}px)`; a.style.opacity = 1 - k;
        const b = q(el, '[data-b]'); b.style.opacity = k; b.style.transform = `translateY(${(1 - k) * 300}px)`;
        if (extra) pop(q(el, '[data-x]'), lt, sw + .55 * (d - sw), .4);
      },
      sfx: (d, S) => { const sw = swap(d, S); return [[.05, 'pop'], [Math.min(1.2, sw * .4), 'ding'], [sw, 'whoosh'], ...(extra ? [[sw + .55 * (d - sw), 'pop']] : [])]; },
    };
  },

  cta(top, sub) {
    return {
      bg: 'em',
      html: `<div class="col" style="gap:30px;position:relative">
        <div data-top>${top}</div>
        <div data-l class="col" style="gap:4px;opacity:0"><div class="serif ltr" style="font-size:150px;line-height:1">OT1-Pro</div>
          <div style="font-weight:900;font-size:46px">شريكك في المبيعات، <span style="color:var(--yellow)">مش شات بوت</span></div></div>
        <div data-btn style="position:relative;opacity:0"><div class="cta-btn">
          <svg width="62" height="62" viewBox="0 0 24 24"><path fill="#052e16" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.8s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3z"/></svg>
          ابعتلنا على واتساب</div><div class="ring2" data-r1></div><div class="ring2" data-r2></div></div>
        <div data-n class="ltr" style="opacity:0;font-weight:900;font-size:46px;letter-spacing:1px">+20 102 636 1218</div>
        <div data-s style="opacity:0;font-weight:800;font-size:32px;color:#d1fae5">${sub}</div>
      </div>`,
      update(el, lt, d, S) {
        const top = q(el, '[data-top]'); if (S.topAnim) S.topAnim(top, lt, d);
        const a = S.ctaAt || 0; pop(q(el, '[data-l]'), lt, a, .45); const btn = q(el, '[data-btn]'); pop(btn, lt, a + .6, .4);
        if (lt > a + 1.1) btn.style.transform = `scale(${1 + .03 * Math.sin((lt - a - 1.1) * 5)})`;
        [['[data-r1]', a + 1.2], ['[data-r2]', a + 2.1]].forEach(([s, b]) => { const r = q(el, s), ph = lt < b ? -1 : ((lt - b) % 1.8) / 1.8; r.style.opacity = ph < 0 ? 0 : (1 - ph) * .8; r.style.transform = `scale(${1 + Math.max(ph, 0) * .25})`; });
        pop(q(el, '[data-n]'), lt, a + 1, .35); pop(q(el, '[data-s]'), lt, a + 1.3, .35);
      },
    };
  },

  bars(title, rows, color) {
    return `<div class="card" style="width:100%;background:#fff;color:var(--ink)"><div style="font-weight:900;font-size:40px;margin-bottom:20px">${title}</div>
      <div class="col" style="gap:20px;align-items:stretch">${rows.map(r => `<div class="bar-row"><span>${r[0]}</span><div class="bar-track"><div class="bar-fill" data-v="${r[1]}" style="background:${color}"></div></div><span class="ltr serif" data-n="${r[1]}" style="font-size:46px">0%</span></div>`).join('')}</div></div>`;
  },
  growBars(card, lt, a, d = 1) {
    qa(card, '.bar-fill').forEach((f, i) => { const k = eo(p(lt, a + i * .2, d)); f.style.width = (+f.dataset.v * k) + '%'; });
    qa(card, '[data-n]').forEach((n, i) => { const k = eo(p(lt, a + i * .2, d)); n.textContent = Math.round(+n.dataset.n * k) + '%'; });
  },
};
