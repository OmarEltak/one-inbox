/* Scenes shared by both ads. Each factory returns {html, update(el, lt, dur)}. */
window.SH = {
  /* "Partners, not software": 90-min build call -> 4 weekly tunings -> 30 days co-admin */
  partner(headline) {
    const steps = [
      ['مكالمة 90 دقيقة', 'نبني الـ AI معاك بأسلوبك'],
      ['أسبوع 1', 'تظبيط'], ['أسبوع 2', 'تظبيط'], ['أسبوع 3', 'تظبيط'], ['أسبوع 4', 'تظبيط'],
      ['30 يوم', 'معاك كـ co-admin على صفحتك'],
    ];
    return {
      html: `<div class="col" style="gap:34px;width:900px">
        <div class="h2" data-h>${headline}</div>
        <div class="card" style="width:100%;padding:34px 40px;position:relative">
          <div data-rail style="position:absolute;top:58px;bottom:58px;right:63px;width:6px;border-radius:6px;background:var(--line)"><div data-fill style="width:100%;height:0;background:var(--mint);border-radius:6px"></div></div>
          ${steps.map((s, i) => `<div data-s="${i}" class="row" style="gap:28px;padding:9px 0;opacity:0">
            <div style="width:52px;height:52px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;background:${i === 0 || i === 5 ? 'var(--mint)' : 'var(--card2)'};border:3px solid var(--mint);position:relative;z-index:2;font-weight:900;font-size:24px;color:var(--ink)">${i === 0 || i === 5 ? '✓' : ''}</div>
            <div style="font-weight:900;font-size:${i === 0 || i === 5 ? 40 : 32}px">${s[0]}</div>
            <div class="muted" style="font-weight:700;font-size:28px">${s[1]}</div></div>`).join('')}
        </div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-h]'), lt, 0);
        const span = Math.max(d - 1.6, 3);
        qa(el, '[data-s]').forEach((s, i) => slideX(s, lt, .5 + i * span / 6, .4, -60));
        q(el, '[data-fill]').style.height = (cl((lt - .5) / span) * 100) + '%';
      },
    };
  },

  /* Pilot card first, then the Founding Partner card slides over it at `swapAt` (fraction of scene) */
  pricing(swapAt, extraLine) {
    return {
      html: `<div style="position:relative;width:880px;height:760px">
        <div data-a class="price card" style="position:absolute;top:0;left:0">
          <div class="lbl">AI PILOT · تجربة 14 يوم</div>
          <div class="name">جرب الأول</div>
          <div style="margin:10px 0 16px"><span class="amt">3,500</span> <span class="unit">جنيه · مرة واحدة</span></div>
          <div class="col" style="align-items:flex-start;gap:12px">
            <div class="check">صفحة ومنصة · 14 يوم متابعة يومية</div>
            <div class="check">تظبيط الردود كل يوم</div>
          </div>
          <div data-shield class="row" style="margin-top:22px;background:rgba(52,211,153,.12);border:2px solid var(--mint);border-radius:22px;padding:16px 22px;gap:16px">
            <svg width="54" height="60" viewBox="0 0 24 26"><path d="M12 1 2 5v7c0 6.5 4.3 11.4 10 13 5.7-1.6 10-6.5 10-13V5z" fill="#34d399"/><path d="m7 13 3.4 3.4L17 9.8" stroke="#0a1814" stroke-width="2.6" fill="none" stroke-linecap="round"/></svg>
            <div style="font-weight:800;font-size:30px;line-height:1.35">فلوسك ترجعلك كاملة<br><span class="muted" style="font-size:25px">لو ما اتعاملش مع 50 محادثة حقيقية</span></div>
          </div>
        </div>
        <div data-b class="price light" style="position:absolute;top:70px;left:0;opacity:0">
          <div class="tag" style="display:inline-block;background:var(--em);color:#fff">أغلب الشركا بيبدأوا هنا</div>
          <div class="lbl" style="margin-top:12px">FOUNDING PARTNER</div>
          <div class="name">الشريك المؤسس</div>
          <div style="margin:8px 0 4px"><span class="amt">7,500</span> <span class="unit">جنيه إعداد</span></div>
          <div style="font-weight:900;font-size:46px;color:var(--em-deep)">+ <span class="ltr">2,500</span> جنيه في الشهر</div>
          <div class="col" style="align-items:flex-start;gap:10px;margin-top:16px">
            <div class="check">لحد 3 صفحات على كل المنصات</div>
            <div class="check">AI مبني مخصوص ليك · 30 يوم متابعة</div>
            <div class="check">تقدر تلغي في أي وقت</div>
          </div>
          ${extraLine ? `<div data-x style="margin-top:18px;font-weight:900;font-size:34px;background:var(--ink);color:var(--mint);border-radius:18px;padding:12px 20px;opacity:0">${extraLine}</div>` : ''}
        </div></div>`,
      update(el, lt, d, S) {
        const sw = typeof swapAt === 'object' ? S.rel(swapAt.line) : swapAt * d;
        pop(q(el, '[data-a]'), lt, 0);
        pop(q(el, '[data-shield]'), lt, .35 * sw, .45);
        const k = eo(p(lt, sw, .55));
        const a = q(el, '[data-a]'); a.style.transform = `scale(${1 - .08 * k}) translateY(${-40 * k}px)`; a.style.opacity = 1 - .65 * k;
        const b = q(el, '[data-b]'); b.style.opacity = k; b.style.transform = `translateY(${(1 - k) * 260}px)`;
        if (extraLine) pop(q(el, '[data-x]'), lt, sw + .6 * (d - sw), .4);
      },
    };
  },

  /* "Anything you dream of, we build it": chips + White-Glove */
  custom(title, chips) {
    return {
      html: `<div class="col" style="gap:30px;width:940px">
        <div class="h2" data-h>${title}</div>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:18px">
          ${chips.map((c, i) => `<div data-c="${i}" class="pill" style="opacity:0;font-size:34px">${c}</div>`).join('')}
          <div data-c="${chips.length}" class="pill" style="opacity:0;font-size:34px;border-style:dashed;border-color:var(--mint);color:var(--mint)">+ أي فكرة في دماغك</div>
        </div>
        <div data-w class="card" style="opacity:0;text-align:center;background:var(--card2)">
          <div class="lbl muted" style="font-weight:800;font-size:24px">WHITE-GLOVE · MULTI-BRAND / CUSTOM</div>
          <div style="font-weight:900;font-size:44px">باقة مخصوص · بنسعّرها على قد شغلك</div>
        </div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-h]'), lt, 0);
        const n = chips.length + 1;
        qa(el, '[data-c]').forEach((c, i) => pop(c, lt, .4 + i * (d * .55) / n, .35, 30));
        pop(q(el, '[data-w]'), lt, d * .68, .45);
      },
    };
  },

  cta(sub) {
    return {
      html: `<div class="col" style="gap:34px;position:relative">
        <div data-l class="col" style="gap:6px">
          <div class="serif" style="font-size:150px;line-height:1">OT1-Pro</div>
          <div style="font-weight:900;font-size:48px">شريكك في المبيعات، <span class="mint">مش شات بوت</span></div>
        </div>
        <div data-btn style="position:relative">
          <div class="cta-btn">
            <svg width="62" height="62" viewBox="0 0 24 24"><path fill="#052e16" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.8s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3z"/></svg>
            ابعتلنا على واتساب
          </div>
          <div class="ring" data-r1></div><div class="ring" data-r2></div>
        </div>
        <div data-n class="ltr" style="font-weight:800;font-size:46px;letter-spacing:1px">+20 102 636 1218</div>
        <div data-s class="muted" style="font-weight:700;font-size:32px">${sub}</div>
      </div>`,
      update(el, lt, d) {
        pop(q(el, '[data-l]'), lt, 0, .5);
        const btn = q(el, '[data-btn]'); pop(btn, lt, .9, .45);
        const pulse = 1 + .035 * Math.sin(Math.max(0, lt - 1.4) * 5); if (lt > 1.4) btn.style.transform = `scale(${pulse})`;
        [['[data-r1]', 1.5], ['[data-r2]', 2.4]].forEach(([s, a]) => {
          const r = q(el, s), ph = lt < a ? -1 : ((lt - a) % 1.8) / 1.8;
          const box = btn.firstElementChild; const w = box.offsetWidth, h = box.offsetHeight;
          if (ph < 0) { r.style.opacity = 0; return; }
          r.style.width = w + 'px'; r.style.height = h + 'px'; r.style.left = 0; r.style.top = 0; r.style.borderRadius = '999px';
          r.style.transform = `scale(${1 + ph * .25})`; r.style.opacity = (1 - ph) * .8;
        });
        pop(q(el, '[data-n]'), lt, 1.4, .4); pop(q(el, '[data-s]'), lt, 1.8, .4);
      },
    };
  },

  /* horizontal bar chart card */
  bars(title, rows, color) {
    return `<div class="card" style="width:100%"><div style="font-weight:900;font-size:36px;margin-bottom:18px">${title}</div>
      <div class="bars">${rows.map(r => `<div class="bar-row"><span>${r[0]}</span><div class="bar-track"><div class="bar-fill" data-v="${r[1]}" style="background:${color || 'var(--mint)'}"></div></div><span class="ltr" data-n="${r[1]}" style="font-family:Serif;font-size:40px">0%</span></div>`).join('')}</div></div>`;
  },
  growBars(card, lt, a, d = 1.1) {
    qa(card, '.bar-fill').forEach((f, i) => { const k = eo(p(lt, a + i * .18, d)); f.style.width = (+f.dataset.v * k) + '%'; });
    qa(card, '[data-n]').forEach((n, i) => { const k = eo(p(lt, a + i * .18, d)); n.textContent = Math.round(+n.dataset.n * k) + '%'; });
  },
};
