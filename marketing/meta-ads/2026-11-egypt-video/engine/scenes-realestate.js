/* Ad B — Real estate (Egypt): "same lead, two endings" split screen. Scene `line` = voice line in scripts/realestate.txt */
(() => {
const B = C.bubble;
const lead = 'الشقة دي سعرها كام؟ وفيه تقسيط؟ 🏠';

window.AD = {
  captions: [
    '*نفس* الليد. *نفس* الرسالة. *نفس* الساعة… 11 بالليل.',
    'مرة رد عليه *السيلز…* ومرة رد عليه *OT1-Pro.* شوف الفرق.',
    'الليد بيسأل: «الشقة دي سعرها كام؟ وفيه تقسيط؟»',
    'السيلز *نايم.* طبيعي… ده بني آدم.',
    '*OT1-Pro* رد *في* *ثواني،* وبعتله السعر ونظام السداد والبروشور.',
    'وبعدها سأله أسئلة السيلز بتاعك: *الميزانية* كام؟ في أنهي *منطقة؟* كاش ولا *تقسيط؟* والاستلام إمتى؟',
    'الليد بعت *فويس* *نوت…* اتسمع، واترد عليه.',
    'الساعة 12:30: الليد نزل في *الشيت* *بتاعك،* بسكور *92* من 100، وملاحظة: «جاد، ميزانيته جاهزة، وعايز معاينة الأسبوع ده».',
    'الساعة 1 الضهر تاني يوم… السيلز صحي ورد: «أهلاً يا فندم، السعر في الخاص». الليد *ما* *ردش.*',
    'كان اتكلم مع *3* *شركات* *غيرك.*',
    'الفرق مش في الإعلان. الفرق في *أول* *ساعة.*',
    'وكمان بيقولك أكتر *اعتراض* بيوقف عملاك: السعر؟ المكان؟ المُقَدَّم؟ عشان إعلانك الجاي يبقى *أذكى.*',
    'وإحنا *شركا،* مش أداة. بنبنيه معاك على مشاريعك في مكالمة *90* *دقيقة،* ونفضل معاك *30* *يوم* نظبطه.',
    'جرّب 14 يوم بـ *3,500* *جنيه،* بضمان استرداد كامل. والشريك المؤسس: *7,500* إعداد + *2,500* في الشهر.',
    'دوس على الزرار وكلمنا على *الواتساب.* OT1-Pro… شريكك في المبيعات، *مش* *شات* *بوت.*',
  ],
  scenes: [
    { line: 0, bg: 'ink', noWhoosh: true, // same lead, same message, same hour
      html: `<div class="col" style="gap:8px">
        ${['نفس الليد.', 'نفس الرسالة.', 'نفس الساعة…'].map((t, i) => `<div data-l="${i}" class="poster" style="opacity:0;font-size:150px;color:${i === 2 ? 'var(--yellow)' : '#fff'}">${t}</div>`).join('')}
        <div data-c class="serif ltr" style="opacity:0;font-size:200px;line-height:1;color:var(--yellow)">23:04</div></div>`,
      update(el, lt, d) { const s = d / 4.2; qa(el, '[data-l]').forEach((l, i) => slam(l, lt, .05 + i * s, i % 2 ? 3 : -3)); pop(q(el, '[data-c]'), lt, .05 + 3 * s, .3); },
      sfx: d => [0, 1, 2].map(i => [.05 + i * d / 4.2, 'stamp']).concat([[.05 + 3 * d / 4.2, 'tick']]) },

    { line: 1, bg: 'ink', // ---- the split screen runs lines 1–9 ----
      html: `<div class="split">
        <div class="side" data-side="sales">
          <div class="hd" style="background:var(--red)"><span>😴 السيلز</span><span class="clk" data-ck>23:04</span></div>
          <div class="msgs">
            ${B(['in', lead, false, '23:04'])}
            ${B(['out', 'أهلاً يا فندم، السعر في الخاص', false, '13:02 · تاني يوم'])}
            <div class="b in" style="background:transparent;box-shadow:none;color:#6b7280;font-size:20px;padding:0 6px">✓✓ اتشافت · مفيش رد</div>
          </div>
          <div class="ov" data-sleep style="background:rgba(11,15,14,.6);flex-direction:column;gap:6px;color:#fff"><div style="font-size:110px">😴</div><div style="font-weight:900;font-size:34px">آخر ظهور 6:12 م</div></div>
          <div class="ov" data-lost style="background:rgba(232,32,42,.88);flex-direction:column;gap:10px;color:#fff;text-align:center"><div style="font-size:120px;line-height:1">✗</div><div style="font-weight:900;font-size:40px;line-height:1.25">اتكلم مع<br>3 شركات غيرك</div></div>
        </div>
        <div class="side" data-side="ai">
          <div class="hd" style="background:var(--em2)"><span>⚡ OT1-Pro</span><span class="clk" data-ck>23:04</span></div>
          <div class="msgs">
            ${B(['in', lead, false, '23:04'])}
            ${B(['out', 'أهلاً بحضرتك 👋 الشقة 3 غرف من 4.2 مليون، وتقسيط لحد 8 سنين', true, '23:04'])}
            ${B(['out', '📄 بروشور المشروع.pdf', true])}
            ${B(['out', 'الميزانية كام تقريباً؟ وتحب أنهي منطقة؟', true])}
            ${B(['in', '4 لـ 5 مليون، التجمع'])}
            ${B(['out', 'كاش ولا تقسيط؟ والاستلام إمتى؟', true])}
            ${B(['in', C.voice('0:09', 120)])}
            ${B(['out', 'سمعتك 👌 هبعتلك مواعيد المعاينة', true, '23:41'])}
          </div>
          <div class="ov" data-win style="align-items:flex-end;padding:14px">
            <div style="width:100%;background:#fff;border-radius:26px;padding:16px 18px;box-shadow:0 -10px 40px rgba(0,0,0,.25)">
              <div style="font-weight:900;font-size:24px;color:#14532d;background:#e9f5ee;border-radius:12px;padding:4px 12px;display:inline-block">✓ نزل في الشيت</div>
              <div class="row" style="gap:14px;margin-top:10px">${C.ring(92, '#00B67A')}<div style="font-weight:800;font-size:22px;line-height:1.4;color:#374151"><b style="color:var(--em2)">ملاحظة AI:</b> جاد، ميزانيته جاهزة، وعايز معاينة الأسبوع ده</div></div>
            </div></div>
        </div></div>`,
      update(el, lt, d, S) {
        const sales = q(el, '[data-side="sales"]'), ai = q(el, '[data-side="ai"]');
        slideX(sales, lt, 0, .45, 300); slideX(ai, lt, .1, .45, -300);
        const r = i => S.rel(i), re = i => S.relEnd(i);
        bub(qa(sales, '.b')[0], lt, r(2) + .3); bub(qa(ai, '.b')[0], lt, r(2) + .3);
        const ab = qa(ai, '.b');
        [r(4) + .3, r(4) + 1.2, r(5) + .2, r(5) + .2 + (re(5) - r(5)) * .35, r(5) + .2 + (re(5) - r(5)) * .6, r(6) + .1, r(6) + .2 + (re(6) - r(6)) * .55]
          .forEach((t, i) => bub(ab[i + 1], lt, t));
        qa(ai, '[data-ck]')[0].textContent = lt < r(5) ? '23:04' : lt < r(6) ? '23:12' : lt < r(7) ? '23:41' : '00:30';
        const win = q(ai, '[data-win]'), kw = eo(p(lt, r(7) + .2, .4)); win.style.opacity = kw; win.firstElementChild.style.transform = `translateY(${(1 - kw) * 200}px)`;
        const arc = q(win, '[data-arc]'), c = 2 * Math.PI * 50, ks = eo(p(lt, r(7) + .6, 1)); arc.setAttribute('stroke-dashoffset', c * (1 - .92 * ks)); q(win, '[data-sv]').textContent = Math.round(92 * ks);
        const wake = p(lt, r(8) + .2, .3);
        q(sales, '[data-sleep]').style.opacity = Math.min(p(lt, r(3) + .1, .3), 1 - wake);
        qa(sales, '[data-ck]')[0].textContent = wake > .5 ? '13:02' : '23:04';
        const sb = qa(sales, '.b'); bub(sb[1], lt, r(8) + (re(8) - r(8)) * .45); bub(sb[2], lt, re(8) - .6);
        const lost = q(sales, '[data-lost]'), kl = p(lt, r(9) + .05, .25); lost.style.opacity = kl; lost.firstElementChild.style.transform = `scale(${2 - eo(kl)})`;
      },
      sfx: (d, S) => { const r = i => S.rel(i), re = i => S.relEnd(i);
        return [[r(2) + .3, 'pop'], [r(3) + .1, 'tick'], [r(4) + .3, 'pop'], [r(4) + 1.2, 'pop'], [r(5) + .2, 'pop'], [r(5) + .2 + (re(5) - r(5)) * .35, 'pop'], [r(5) + .2 + (re(5) - r(5)) * .6, 'pop'],
          [r(6) + .1, 'pop'], [r(6) + .2 + (re(6) - r(6)) * .55, 'pop'], [r(7) + .2, 'whoosh'], [r(7) + .6, 'ding'], [r(8) + (re(8) - r(8)) * .45, 'pop'], [r(9) + .05, 'stamp']]; } },

    { line: 10, bg: 'ink', // the first hour
      html: `<div class="col" style="gap:24px">
        <div data-a class="h1" style="font-size:80px">الفرق مش في <span style="position:relative;display:inline-block">الإعلان<i data-x style="position:absolute;left:-6px;right:-6px;top:52%;height:10px;background:var(--red);border-radius:6px;transform-origin:right;transform:scaleX(0)"></i></span></div>
        <div data-b class="poster" style="font-size:150px;color:var(--yellow);opacity:0">في أول ساعة</div>
        <svg data-c width="290" height="290" viewBox="0 0 120 120" style="opacity:0"><circle cx="60" cy="60" r="52" stroke="rgba(255,255,255,.15)" stroke-width="10" fill="none"/><circle data-arc cx="60" cy="60" r="52" stroke="#FFD60A" stroke-width="10" fill="none" stroke-linecap="round" transform="rotate(-90 60 60)" stroke-dasharray="326.7" stroke-dashoffset="0"/><text data-tm x="60" y="70" text-anchor="middle" fill="#fff" font-family="Serif" font-size="30">60:00</text></svg></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-a]'), lt, 0); strike(q(el, '[data-x]'), lt, .8);
        slam(q(el, '[data-b]'), lt, d * .45, -3); pop(q(el, '[data-c]'), lt, d * .5, .35);
        const k = cl((lt - d * .5) / (d * .5)); q(el, '[data-arc]').setAttribute('stroke-dashoffset', 326.7 * k);
        const s = Math.round(3600 * (1 - k * .9)); q(el, '[data-tm]').textContent = `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
      },
      sfx: d => [[.8, 'tick'], [d * .45, 'stamp']] },

    { line: 11, bg: 'paper', // objections
      html: `<div class="col" style="gap:24px;width:940px;position:relative">
        <span class="chip" style="font-size:24px">مثال توضيحي</span>
        <div data-a style="width:100%">${SH.bars('أكتر اعتراض بيوقف عملاك', [['السعر', 42], ['المكان', 27], ['المُقَدَّم', 19]], 'var(--red)')}</div>
        <div data-b class="pill" style="opacity:0;font-size:44px;background:var(--ink);color:var(--yellow);border:none;padding:14px 40px">🎯 إعلانك الجاي يبقى أذكى</div></div>`,
      update(el, lt, d) { pop(q(el, '[data-a]'), lt, 0); SH.growBars(q(el, '[data-a]'), lt, .4); pop(q(el, '[data-b]'), lt, d * .72, .35); },
      sfx: d => [[.4, 'pop'], [.6, 'pop'], [.8, 'pop'], [d * .72, 'ding']] },

    { line: 12, ...SH.partner('إحنا <span class="em">شركا</span>، مش أداة') },
    { line: 13, ...SH.pricing(.5) },
    { line: 14, ...SH.cta('', 'ابعت كلمة «عقارات» ونحدد معاك مكالمة الـ 90 دقيقة') },
  ],
};
})();
