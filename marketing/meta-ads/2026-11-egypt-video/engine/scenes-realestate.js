/* Ad B — Real estate (Egypt). Scene i starts on voice line `line` (see scripts/realestate.txt). */
(() => {
const quals = [['الميزانية', '4 – 5 مليون'], ['المنطقة', 'التجمع الخامس'], ['النوع', 'شقة 3 غرف'], ['الدفع', 'تقسيط'], ['الاستلام', 'خلال سنة']];
const leads = [['كريم م.', 94, 'hot', 'سخن'], ['محمد ع.', 88, 'hot', 'سخن'], ['أحمد س.', 61, 'warm', 'دافي'], ['يوسف ح.', 57, 'warm', 'دافي'], ['مصطفى ر.', 22, 'cold', 'بارد'], ['عمرو ط.', 15, 'cold', 'بارد']];

window.AD = {
  captions: [
    'بتصرف *آلافات* على إعلانات فيسبوك عشان الليدز…',
    'ليد يبعت الساعة 11 بالليل: «الشقة دي سعرها كام؟ وفيه تقسيط؟» والسيلز يرد *تاني* *يوم* *الضهر.*',
    'ساعتها… يكون كلّم *3* *شركات* *غيرك.*',
    'المشكلة مش في الإعلان. المشكلة في *أول* *ساعة* بعد الإعلان.',
    '*OT1-Pro* بيرد على كل ليد *في* *ثواني،* 24 ساعة، على الواتساب والماسنجر والإنستجرام… وحتى على *كومنتات* *الإعلان.*',
    'ومش بيرد وخلاص… بيسأل نفس أسئلة السيلز بتاعك: *الميزانية* كام؟ في أنهي *منطقة؟* شقة ولا فيلا؟ كاش ولا *تقسيط؟* والاستلام إمتى؟',
    'بيبعتله تفاصيل المشروع ونظام السداد، بيسمع *الفويس* *نوت،* وبيرد بأسلوب شركتك.',
    'وبعدها بيقسّم الليدز: *سخن،* *ودافي،* *وبارد.* فريقك يكلم *الجاهز* *للشرا* الأول… بدل ما يضيع يومه مع اللي بيسأل وخلاص.',
    'وكل ليد جاهز بينزل في *الشيت* *بتاعك:* الاسم، والرقم، والميزانية، والمنطقة. السيلز يمسك التليفون… *ويقفل.*',
    'وكمان بيقولك أنهي مشروع عليه طلب، وإيه أكتر *اعتراض* بيوقف العملا: السعر؟ المكان؟ المقدم؟ عشان إعلانك الجاي يبقى *أذكى.*',
    'وإحنا مش أداة وخلاص. إحنا *شركا.* بنبني الذكاء الاصطناعي معاك في مكالمة *90* *دقيقة،* على مشاريعك وأسعارك، ونفضل معاك *30* *يوم* نظبطه أسبوع بأسبوع.',
    'تقدر تبدأ بتجربة 14 يوم بـ *3,500* *جنيه،* ولو ما اتعاملش مع 50 محادثة حقيقية، *فلوسك* *ترجعلك.* وباقة الشريك: *7,500* إعداد + *2,500* في الشهر.',
    'عندك كذا مشروع؟ محتاج الليدز تروح على *الـCRM* بتاعك؟ دي باقة مخصوص، بنسعّرها على قد شغلك.',
    'دوس على الزرار وكلمنا على *الواتساب.* OT1-Pro… شريكك في المبيعات، *مش* *شات* *بوت.*',
  ],
  scenes: [
    { line: 0, // ad spend ticking up
      html: `<div class="col" style="gap:26px;width:900px">
        <div class="card" style="width:100%;position:relative">
          <div class="example" style="top:-18px">مثال توضيحي</div>
          <div class="row" style="justify-content:space-between"><div style="font-weight:800;font-size:30px" class="muted">حملة: كمبوند · التجمع الخامس</div><div class="tag" style="background:rgba(24,119,242,.2);color:#93c5fd">Facebook Ads</div></div>
          <div class="row" style="justify-content:space-between;margin-top:22px">
            <div><div class="muted" style="font-weight:700;font-size:28px">الإنفاق</div><div style="font-family:Serif;font-size:118px;line-height:1" class="ltr" data-sp>0</div><div style="font-weight:800;font-size:30px">جنيه</div></div>
            <div style="text-align:end"><div class="muted" style="font-weight:700;font-size:28px">ليدز</div><div style="font-family:Serif;font-size:118px;line-height:1" class="ltr mint" data-ld>0</div><div style="font-weight:800;font-size:30px">رسالة</div></div>
          </div></div>
        <div data-q class="h2" style="opacity:0">مين بيرد عليهم؟</div></div>`,
      update(el, lt, d) {
        const k = eo(p(lt, .2, d - .3));
        q(el, '[data-sp]').textContent = fmt(48750 * k); q(el, '[data-ld]').textContent = fmt(312 * k);
        pop(q(el, '[data-q]'), lt, d * .7, .4);
      } },
    { line: 1, // 11 PM notification -> answered next day noon
      html: `<div style="position:relative">${C.lock('<span data-t1>23:04</span><span data-t2 style="position:absolute;left:0;right:0;opacity:0">12:40</span>', '<span data-d1>الخميس</span><span data-d2 style="position:absolute;left:0;right:0;opacity:0">الجمعة · تاني يوم</span>', 'WhatsApp · كريم', 'الشقة دي سعرها كام؟ وفيه تقسيط؟')}
        <div data-late class="pill" style="position:absolute;bottom:120px;left:50%;transform:translateX(-50%);white-space:nowrap;opacity:0;background:rgba(251,113,133,.15);border-color:var(--red);color:var(--red)">رد السيلز: بعد 13 ساعة</div></div>`,
      update(el, lt, d) {
        pop(q(el, '.notif'), lt, .4, .45);
        const sw = d * .62, k = p(lt, sw, .5);
        q(el, '[data-t1]').style.opacity = 1 - k; q(el, '[data-t2]').style.opacity = k;
        q(el, '[data-d1]').style.opacity = 1 - k; q(el, '[data-d2]').style.opacity = k;
        pop(q(el, '[data-late]'), lt, sw + .4, .4);
      } },
    { line: 2, // competitors already replied
      html: `<div class="col" style="gap:20px;width:880px">
        ${[['شركة 1', 'ردت بعد دقيقتين'], ['شركة 2', 'بعتت البروشور'], ['شركة 3', 'حجزت معاه معاينة']].map((r, i) => `<div data-r="${i}" class="lead" style="opacity:0"><div class="avatar" style="width:64px;height:64px;border-radius:50%;background:var(--card2);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:30px;color:var(--mint)">${i + 1}</div><div class="nm">${r[0]}<div class="muted" style="font-size:26px">${r[1]}</div></div><span class="badge hot" style="background:rgba(52,211,153,.15);color:var(--mint);border-color:var(--mint)">✓</span></div>`).join('')}
        <div data-r="3" class="lead" style="opacity:0;border-color:var(--red)"><div class="nm" style="color:var(--red)">انت<div class="muted" style="font-size:26px">لسه ما ردتش</div></div><span class="badge hot">اتأخرت</span></div></div>`,
      update(el, lt, d) { qa(el, '[data-r]').forEach((r, i) => slideX(r, lt, .1 + i * (d - .6) / 4, .35, -80)); } },
    { line: 3, // the first hour
      html: `<div class="col" style="gap:30px">
        <div data-a class="h1">المشكلة مش في <span style="position:relative;display:inline-block">الإعلان<i data-x style="position:absolute;left:-6px;right:-6px;top:52%;height:9px;background:var(--red);border-radius:6px;transform-origin:right;transform:scaleX(0)"></i></span></div>
        <div data-b class="h1" style="opacity:0">المشكلة في <span class="mint">أول ساعة</span></div>
        <svg data-c width="300" height="300" viewBox="0 0 120 120" style="opacity:0"><circle cx="60" cy="60" r="52" stroke="#24443c" stroke-width="10" fill="none"/><circle data-arc cx="60" cy="60" r="52" stroke="#34d399" stroke-width="10" fill="none" stroke-linecap="round" transform="rotate(-90 60 60)" stroke-dasharray="326.7" stroke-dashoffset="0"/><text data-tm x="60" y="70" text-anchor="middle" fill="#f6f3ec" font-family="Serif" font-size="30">60:00</text></svg></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-a]'), lt, 0); q(el, '[data-x]').style.transform = `scaleX(${eo(p(lt, .9, .35))})`;
        pop(q(el, '[data-b]'), lt, d * .42, .4); pop(q(el, '[data-c]'), lt, d * .5, .4);
        const k = cl((lt - d * .5) / (d * .5)); q(el, '[data-arc]').setAttribute('stroke-dashoffset', 326.7 * k);
        const s = Math.round(3600 * (1 - k * .9)); q(el, '[data-tm]').textContent = `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
      } },
    { line: 4, // instant replies everywhere
      html: `<div class="col" style="gap:34px">
        <div data-a class="col" style="gap:0"><div class="serif" style="font-size:120px;line-height:1">OT1-Pro</div><div class="h3">بيرد على كل ليد</div></div>
        <div data-w class="row" style="gap:20px;opacity:0"><div class="serif ltr mint" style="font-size:150px;line-height:1" data-sw>00:00</div><div style="font-weight:900;font-size:42px;line-height:1.2">رد<br>في ثواني</div></div>
        <div data-p class="pill" style="font-size:44px;opacity:0;background:var(--em);color:var(--ink);border-color:var(--em)">24 ساعة · 7 أيام</div>
        <div class="row" style="gap:14px;flex-wrap:wrap;justify-content:center;width:940px">
          ${[['واتساب', 'var(--wa)'], ['ماسنجر', 'var(--fb)'], ['إنستجرام', 'var(--ig)'], ['كومنتات الإعلان', 'var(--amber)']].map((c, i) => `<div data-c="${i}" class="pill" style="opacity:0"><i style="width:18px;height:18px;border-radius:50%;background:${c[1]};display:inline-block"></i>${c[0]}</div>`).join('')}
        </div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-a]'), lt, 0); pop(q(el, '[data-w]'), lt, .9, .4);
        const s = Math.min(3, Math.max(0, lt - 1.1) * 2.2); q(el, '[data-sw]').textContent = '00:0' + Math.floor(s);
        pop(q(el, '[data-p]'), lt, d * .3, .4);
        qa(el, '[data-c]').forEach((c, i) => pop(c, lt, d * .45 + i * d * .1, .35, 30));
      } },
    { line: 5, // qualification checklist
      html: `<div class="col" style="gap:22px;width:900px">
        <div data-h class="h2">بيسأل أسئلة <span class="mint">السيلز بتاعك</span></div>
        <div class="card" style="width:100%;padding:18px 32px">
          ${quals.map((r, i) => `<div data-r="${i}" class="row" style="justify-content:space-between;padding:16px 0;border-bottom:${i < 4 ? '2px solid var(--line)' : 'none'};opacity:0">
            <div class="row" style="gap:16px"><span style="width:40px;height:40px;border-radius:50%;border:3px solid var(--mint);display:flex;align-items:center;justify-content:center;font-weight:900;color:var(--ink)" data-k>✓</span><span style="font-weight:800;font-size:36px">${r[0]}؟</span></div>
            <span data-v style="font-weight:900;font-size:36px;color:var(--mint);opacity:0">${r[1]}</span></div>`).join('')}
        </div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-h]'), lt, 0);
        qa(el, '[data-r]').forEach((r, i) => {
          const a = .5 + i * (d - 1.4) / 5; slideX(r, lt, a, .35, -50);
          const k = p(lt, a + .5, .3); r.querySelector('[data-v]').style.opacity = k;
          const ck = r.querySelector('[data-k]'); ck.style.background = k > .5 ? 'var(--mint)' : 'transparent'; ck.style.color = k > .5 ? 'var(--ink)' : 'transparent';
        });
      } },
    { line: 6, // brochure, payment plan, voice notes
      html: C.phone('أحمد · عميل محتمل', 'WhatsApp', [
        ['in', '<span class="row" style="gap:12px"><b style="width:44px;height:44px;border-radius:50%;background:var(--mint);display:inline-flex;align-items:center;justify-content:center;color:var(--ink)">▶</b><svg width="190" height="40" viewBox="0 0 190 40">' + Array.from({ length: 30 }, (_, i) => `<rect x="${i * 6.3}" y="${20 - (6 + 14 * Math.abs(Math.sin(i * 1.7)))}" width="3.5" rx="1.5" height="${2 * (6 + 14 * Math.abs(Math.sin(i * 1.7)))}" fill="#9db5ae"/>`).join('') + '</svg><span class="muted ltr" style="font-size:20px">0:14</span></span>', false],
        ['out', 'سمعت رسالتك يا أحمد. ده بروشور المشروع ونظام السداد:'],
        ['out', '<span class="row" style="gap:14px;background:rgba(0,0,0,.2);border-radius:16px;padding:10px 14px"><b style="background:#ef4444;color:#fff;border-radius:8px;padding:4px 10px;font-size:20px">PDF</b>بروشور المشروع.pdf</span>'],
        ['out', 'مقدم 10% وتقسيط على 8 سنين. تحب أحجزلك معاد معاينة؟'],
      ], { example: true }),
      update(el, lt, d) { chatSeq(el, lt, [.2, d * .25, d * .45, d * .65]); } },
    { line: 7, // hot / warm / cold sorting
      html: `<div class="col" style="gap:22px;width:940px">
        <div class="row" style="gap:16px;width:100%">${[['hot', 'سخن'], ['warm', 'دافي'], ['cold', 'بارد']].map(c => `<div class="badge ${c[0]}" style="flex:1;text-align:center;font-size:34px">${c[1]}</div>`).join('')}</div>
        <div class="row" style="gap:16px;width:100%;align-items:flex-start" data-cols>
          ${['hot', 'warm', 'cold'].map(t => `<div style="flex:1;display:flex;flex-direction:column;gap:14px" data-col="${t}">${leads.filter(l => l[2] === t).map(l => `<div class="lead" data-l style="opacity:0;flex-direction:column;align-items:flex-start;gap:4px;padding:16px"><div class="nm" style="font-size:30px">${l[0]}</div><div class="sc ${t === 'hot' ? 'red' : t === 'warm' ? 'amber' : ''}" style="text-align:start;width:auto">${l[1]}</div></div>`).join('')}</div>`).join('')}
        </div>
        <div data-f class="pill" style="opacity:0;font-size:38px;border-color:var(--red);color:var(--red)">فريقك يبدأ من هنا ←</div></div>`,
      update(el, lt, d) {
        qa(el, '[data-l]').forEach((l, i) => pop(l, lt, .4 + i * .32, .35, 50));
        const f = q(el, '[data-f]'); pop(f, lt, d * .5, .4);
        const hot = q(el, '[data-col="hot"]'); const k = p(lt, d * .5, .4);
        qa(hot, '.lead').forEach(c => { c.style.borderColor = k > .5 ? 'var(--red)' : ''; c.style.boxShadow = k > .5 ? '0 0 40px rgba(251,113,133,.25)' : ''; });
      } },
    { line: 8, // google sheet fills up
      html: `<div class="col" style="gap:30px">${C.sheet('ليدز جاهزة · Google Sheet', ['الاسم', 'الرقم', 'الميزانية', 'المنطقة', 'الحالة'], [
        ['كريم م.', '010•••4417', '4–5 مليون', 'التجمع', 'سخن'], ['محمد ع.', '011•••0923', '7 مليون', 'زايد', 'سخن'],
        ['أحمد س.', '012•••5560', '3 مليون', 'أكتوبر', 'دافي'], ['يوسف ح.', '010•••7781', '9 مليون', 'الساحل', 'سخن'],
        ['حسام ك.', '015•••2209', '5 مليون', 'العاصمة', 'دافي']])}
        <div data-call class="pill" style="opacity:0;font-size:40px;background:var(--em);color:var(--ink);border-color:var(--em)">السيلز يمسك التليفون… ويقفل</div></div>`,
      update(el, lt, d) {
        pop(q(el, '.sheet'), lt, 0);
        qa(el, 'tbody tr').forEach((r, i) => { const a = .6 + i * (d * .55) / 5; const k = eo(p(lt, a, .35)); r.style.opacity = k; r.style.transform = `translateX(${(1 - k) * -60}px)`; qa(r, 'td').forEach(td => td.style.background = lt - a < 1.2 ? '#d9f6e8' : '#fff'); });
        pop(q(el, '[data-call]'), lt, d * .72, .4);
      } },
    { line: 9, // insights
      html: `<div class="col" style="gap:24px;width:920px;position:relative">
        <div class="example" style="top:-30px">مثال توضيحي</div>
        <div data-a style="width:100%">${SH.bars('أكتر مشروع عليه طلب', [['كمبوند أ', 46], ['كمبوند ب', 31], ['فيلات ج', 23]])}</div>
        <div data-b style="width:100%;opacity:0">${SH.bars('أكتر اعتراض بيوقف العملا', [['السعر', 42], ['المكان', 27], ['المقدم', 19]], 'var(--amber)')}</div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-a]'), lt, 0); SH.growBars(q(el, '[data-a]'), lt, .3);
        pop(q(el, '[data-b]'), lt, d * .35, .45); SH.growBars(q(el, '[data-b]'), lt, d * .35 + .3);
      } },
    { line: 10, ...SH.partner('إحنا <span class="mint">شركا</span>، مش أداة وخلاص') },
    { line: 11, ...SH.pricing(.58) },
    { line: 12, ...SH.custom('عندك كذا مشروع؟', ['كذا مشروع / كذا براند', 'Zoho CRM', 'HubSpot', 'Salesforce', 'قناة دعم مخصوصة']) },
    { line: 13, ...SH.cta('ابعت كلمة «عقارات» ونحدد معاك مكالمة الـ 90 دقيقة') },
  ],
};
})();
