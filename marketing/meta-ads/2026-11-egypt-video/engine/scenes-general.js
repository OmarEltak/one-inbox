/* Ad A — General (Egypt): «غالي» — the reply after one word decides the sale. Scene `line` = voice line in scripts/general.txt */
(() => {
const bad = (txt, sub) => `<div class="row" style="gap:18px;width:100%;background:#fff;border-radius:26px;padding:18px 24px;color:#111;box-shadow:0 14px 34px rgba(0,0,0,.25);position:relative">
  <div style="flex:1"><div style="font-weight:800;font-size:22px;color:#6b7280">${sub}</div><div style="font-weight:900;font-size:40px">${txt}</div></div>
  <div data-x style="opacity:0;width:84px;height:84px;border-radius:50%;background:var(--red);color:#fff;display:flex;align-items:center;justify-content:center;font-size:56px;font-weight:900;flex:none">✗</div></div>`;

window.AD = {
  captions: [
    'العميل كتبلك كلمة واحدة: *«غالي».* الرد اللي بعدها… هو اللي بيحدد *هتبيع* *ولا* *لأ.*',
    'وأغلب الصفحات بترد كده: «ده آخر سعر يا فندم». «السعر في الخاص». أو… *ولا* *رد.*',
    'والعميل؟ بيقفل الشات، ويشتري *من* *غيرك…* وانت اللي *دافع* تمن الإعلان.',
    'المشكلة إن الموديريتور *بيرد…* بس *مش* *بيبيع.* والشات بوت العادي؟ بيبعت «تم استلام رسالتك» وخلاص.',
    '*OT1-Pro* بيرد زي *أشطر* *بياع* عندك:',
    '«عندك حق، بس دي خامة قطن تقيل ومش بتكش… ولو أخدت اتنين *الشحن* *عليا.* أحجزلك الأسود ولا الكحلي؟»',
    'والعميل: «احجزلي الأسود». *أوردر* *مؤكد…* الساعة 2 بالليل، وانت *نايم.*',
    'وبيسمع *الفويس* *نوت،* ويفهم *الصور،* ويرد على *الكومنتات…* على الواتساب والإنستجرام والفيسبوك، *24* *ساعة.*',
    'وكل عميل بياخد *سكور* من 100، مع *ملاحظة* على كل محادثة: جاد ولا بيتفرج… وليه.',
    'والأوردر الجاهز بينزل في *الشيت* *بتاعك:* الاسم، والرقم، والعنوان. انت بس *تشحن.*',
    'وعشان يبيع *بأسلوبك* *انت،* مش تمبلت جاهز: بنبنيه معاك في مكالمة *90* *دقيقة،* ونفضل معاك *30* *يوم* نظبطه. إحنا *شركاء،* مش برنامج.',
    'جرّبه 14 يوم بـ *3,500* *جنيه،* ولو ما اتعاملش مع 50 محادثة حقيقية… *فلوسك* *ترجعلك* *كاملة.* والشريك المؤسس: *7,500* إعداد + *2,500* في الشهر.',
    'دوس على الزرار وابعتلنا على *الواتساب.* OT1-Pro… شريكك في المبيعات، *مش* *شات* *بوت.*',
  ],
  scenes: [
    { line: 0, bg: 'ink', noWhoosh: true, // hook: one word
      html: `<div class="col" style="gap:40px">
        <div data-b style="opacity:0;background:#fff;color:var(--ink);border-radius:60px;border-start-start-radius:14px;padding:10px 80px 30px;box-shadow:0 30px 90px rgba(0,0,0,.5);position:relative">
          <div style="font-weight:800;font-size:28px;color:#6b7280;text-align:start;padding-top:18px">محمود · WhatsApp · 02:11</div>
          <div class="poster" style="font-size:250px;line-height:1.1">غالي</div></div>
        <div data-dots class="row" style="gap:14px;opacity:0;background:#D9FDD3;border-radius:40px;padding:22px 34px;align-self:flex-end;margin-inline-end:120px">${[0, 1, 2].map(i => `<i data-d="${i}" style="width:22px;height:22px;border-radius:50%;background:#4b5563;display:block"></i>`).join('')}</div>
        <div data-t class="h2" style="opacity:0;color:var(--yellow);font-size:58px">الرد اللي بعدها بيحدد<br>هتبيع ولا لأ</div></div>`,
      update(el, lt, d) {
        const b = q(el, '[data-b]'), k = p(lt, .1, .35); b.style.opacity = k; b.style.transform = `scale(${.5 + .5 * eb(k)})`;
        fade(q(el, '[data-dots]'), lt, d * .35, .3); qa(el, '[data-d]').forEach((x, i) => x.style.transform = `translateY(${-8 * Math.max(0, Math.sin(lt * 7 - i * .8))}px)`);
        pop(q(el, '[data-t]'), lt, d * .45, .4);
      },
      sfx: d => [[.1, 'pop'], [d * .45, 'tick']] },

    { line: 1, bg: 'ink', // the three bad replies
      html: `<div class="col" style="gap:22px;width:900px">
        <div data-h class="h2" style="opacity:0;color:#fff">أغلب الصفحات بترد كده:</div>
        ${[['ده آخر سعر يا فندم', 'موديريتور · بعد 3 ساعات'], ['السعر في الخاص', 'موديريتور · بعد 9 ساعات'], ['✓✓ اتشافت… ولا رد', 'محدش رد']].map((r, i) => `<div data-r="${i}" style="opacity:0;width:100%">${bad(r[0], r[1])}</div>`).join('')}</div>`,
      update(el, lt, d) {
        pop(q(el, '[data-h]'), lt, 0);
        qa(el, '[data-r]').forEach((r, i) => { const a = d * (.22 + i * .24); slideX(r, lt, a, .35, -160); const x = r.querySelector('[data-x]'), k = p(lt, a + .45, .2); x.style.opacity = k; x.style.transform = `scale(${2 - eo(k)})`; });
      },
      sfx: d => [0, 1, 2].flatMap(i => [[d * (.22 + i * .24), 'pop'], [d * (.22 + i * .24) + .45, 'stamp']]) },

    { line: 2, bg: 'ink', // customer leaves, you paid for the ad
      html: `<div class="col" style="gap:30px;width:920px">
        <div data-a class="card" style="opacity:0;width:100%;background:#fff;color:#111;text-align:center">
          <div style="font-weight:800;font-size:28px;color:#6b7280">محمود</div>
          <div style="font-weight:900;font-size:50px">خرج من الشات… واشترى من منافسك</div>
          <div style="font-size:120px;line-height:1.2" data-cart>🛒</div></div>
        <div data-b class="row" style="opacity:0;gap:20px;background:rgba(232,32,42,.15);border:3px solid var(--red);border-radius:28px;padding:20px 34px">
          <div style="font-size:70px">💸</div><div><div style="font-weight:900;font-size:40px;color:#fff">وانت اللي دافع تمن الإعلان</div>
          <div class="ltr serif" style="font-size:64px;color:var(--red);text-align:end" data-sp>0 EGP</div></div></div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-a]'), lt, 0); const c = q(el, '[data-cart]'); c.style.transform = `translateX(${-eo(p(lt, .6, .9)) * 330}px)`; c.style.opacity = 1 - p(lt, 1.2, .3);
        pop(q(el, '[data-b]'), lt, d * .5, .35); q(el, '[data-sp]').textContent = fmt(1850 * eo(p(lt, d * .5, d * .4))) + ' EGP';
      },
      sfx: d => [[.6, 'whoosh'], [d * .5, 'pop']] },

    { line: 3, bg: 'paper', // moderator replies but doesn't sell; normal chatbot
      html: `<div class="col" style="gap:24px;width:940px">
        ${[['👨‍💼', 'الموديريتور', [['بيرد', true], ['بيبيع ويقنع', false], ['24 ساعة', false]]], ['🤖', 'الشات بوت العادي', [['بيرد «تم استلام رسالتك»', true], ['بيفهم العميل', false], ['بيقفل البيعة', false]]]].map((c, i) => `<div data-c="${i}" class="card" style="opacity:0;width:100%;background:#fff;display:flex;gap:24px;align-items:center">
          <div style="font-size:96px">${c[0]}</div><div style="flex:1"><div style="font-weight:900;font-size:44px">${c[1]}</div>
          <div class="row" style="gap:12px;flex-wrap:wrap;margin-top:8px">${c[2].map(x => `<span style="font-weight:900;font-size:28px;border-radius:999px;padding:4px 18px;background:${x[1] ? '#dcfce7' : '#fee2e2'};color:${x[1] ? '#166534' : '#b91c1c'}">${x[1] ? '✓' : '✗'} ${x[0]}</span>`).join('')}</div></div></div>`).join('')}
      </div>`,
      update(el, lt, d) { qa(el, '[data-c]').forEach((c, i) => pop(c, lt, .1 + i * d * .45, .35)); },
      sfx: d => [[.1, 'pop'], [.1 + d * .45, 'pop']] },

    { line: 4, bg: 'em', // the AI sells, live (lines 4–6)
      html: `<div class="row" style="gap:24px">
        <div class="col" style="gap:14px;width:340px;align-items:flex-start">
          <div data-l class="poster" style="opacity:0;font-size:92px;line-height:1.1;color:#fff;text-align:start">زي أشطر<br><span style="color:var(--yellow)">بياع</span><br>عندك</div>
          <div data-ok class="stamp" style="position:static;opacity:0;color:var(--yellow);font-size:60px;border-width:8px;background:rgba(0,0,0,.15)">أوردر مؤكد ✓</div>
          <div data-tm style="opacity:0;font-weight:900;font-size:34px;color:#d1fae5">🌙 02:14 · وانت نايم</div>
        </div>
        ${C.phone('محمود', 'WhatsApp · ⚡ OT1-Pro', [
          ['in', 'الهودي الأسود ده بكام؟', false, '02:10'],
          ['out', 'سعره 850 جنيه يا محمود، ومتاح كل المقاسات 👌', true, '02:10'],
          ['in', '<b style="font-size:34px">غالي</b>', false, '02:11'],
          ['out', 'عندك حق، بس دي خامة قطن تقيل ومش بتكش… ولو أخدت اتنين الشحن عليا 🚚 أحجزلك الأسود ولا الكحلي؟', true, '02:11'],
          ['in', 'احجزلي الأسود 👍', false, '02:14'],
        ])}</div>`,
      update(el, lt, d, S) {
        const r = i => S.rel(i);
        pop(q(el, '[data-l]'), lt, .1, .4);
        chatSeq(el, lt, [0, 0, .3, r(5) + .1, r(6) + .2]);
        const ok = q(el, '[data-ok]'), k = p(lt, r(6) + 1.2, .22); ok.style.opacity = k; ok.style.transform = `rotate(-6deg) scale(${2.2 - 1.2 * eo(k)})`;
        pop(q(el, '[data-tm]'), lt, r(6) + 1.8, .35);
      },
      sfx: (d, S) => [[.3, 'pop'], [S.rel(5) + .1, 'pop'], [S.rel(6) + .2, 'pop'], [S.rel(6) + 1.2, 'stamp'], [S.rel(6) + 1.25, 'ding']] },

    { line: 7, bg: 'paper', // everything else it does
      html: `<div class="col" style="gap:26px;width:960px">
        <div class="row" style="gap:18px">${[['🎤', 'فويس نوت'], ['📷', 'صور'], ['💬', 'كومنتات']].map((x, i) => `<div data-f="${i}" style="opacity:0;width:290px;background:#fff;border-radius:30px;padding:24px;text-align:center;box-shadow:0 14px 34px rgba(0,0,0,.08)"><div style="font-size:100px;line-height:1.1">${x[0]}</div><div style="font-weight:900;font-size:40px">${x[1]}</div></div>`).join('')}</div>
        <div class="row" style="gap:14px">${[['WhatsApp', 'var(--wa)'], ['Instagram', '#E1306C'], ['Facebook', '#1877F2']].map((c, i) => `<div data-p="${i}" class="pill" style="opacity:0;background:#fff;font-size:34px"><i style="width:20px;height:20px;border-radius:50%;background:${c[1]};display:inline-block"></i>${c[0]}</div>`).join('')}</div>
        <div data-h class="poster" style="opacity:0;font-size:130px;color:var(--em2)">24 ساعة</div></div>`,
      update(el, lt, d) {
        qa(el, '[data-f]').forEach((f, i) => pop(f, lt, .1 + i * d * .15, .35));
        qa(el, '[data-p]').forEach((f, i) => pop(f, lt, d * .55 + i * .25, .3, 24)); slam(q(el, '[data-h]'), lt, d * .8, -3);
      },
      sfx: d => [0, 1, 2].map(i => [.1 + i * d * .15, 'pop']).concat([[d * .8, 'stamp']]) },

    { line: 8, bg: 'yellow', // score + AI note
      html: `<div class="col" style="gap:20px;width:940px">
        <div class="h2" data-h style="font-size:56px">سكور + ملاحظة على كل محادثة</div>
        ${C.scoreCard('محمود ع.', 94, 'اتردد في السعر، اقتنع بعرض الشحن وأكد أوردر.', '#00B67A')}
        ${C.scoreCard('كريم س.', 61, 'سأل عن المقاسات مرتين، مستني القبض.', '#F59E0B')}
        ${C.scoreCard('زياد م.', 12, 'بيتفرج بس، ما سألش عن سعر ولا مقاس.', '#9CA3AF')}</div>`,
      update(el, lt, d) { pop(q(el, '[data-h]'), lt, 0); qa(el, '[data-sc]').forEach((c, i) => animScore(c, lt, .5 + i * (d - 1.4) / 3)); },
      sfx: d => [0, 1, 2].map(i => [.5 + i * (d - 1.4) / 3, 'pop']) },

    { line: 9, bg: 'paper', // sheet
      html: `<div class="col" style="gap:28px">${C.sheet('أوردرات جاهزة · Google Sheet', ['الاسم', 'الرقم', 'العنوان', 'الطلب'], [
        ['محمود ع.', '010•••4417', 'مدينة نصر', 'هودي أسود XL'], ['كريم س.', '011•••0923', 'سموحة', 'تيشيرت L'],
        ['أحمد ف.', '012•••5560', 'المنصورة', 'شنطة جلد'], ['حسن ي.', '015•••2209', 'طنطا', '3 تيشيرت']])}
        <div data-ship class="pill" style="opacity:0;font-size:52px;background:var(--ink);color:var(--yellow);border:none;padding:16px 44px">📦 انت بس تشحن</div></div>`,
      update(el, lt, d) {
        pop(q(el, '.sheet'), lt, 0);
        qa(el, 'tbody tr').forEach((r, i) => { const a = .5 + i * (d * .5) / 4, k = eo(p(lt, a, .3)); r.style.opacity = k; r.style.transform = `translateX(${(1 - k) * -80}px)`; qa(r, 'td').forEach(td => td.style.background = lt - a < 1 ? '#d9f6e8' : '#fff'); });
        pop(q(el, '[data-ship]'), lt, d * .78, .35);
      },
      sfx: d => [0, 1, 2, 3].map(i => [.5 + i * (d * .5) / 4, 'pop']).concat([[d * .78, 'ding']]) },

    { line: 10, ...SH.partner('بيبيع <span class="em">بأسلوبك انت</span>') },
    { line: 11, ...SH.pricing(.56) },
    { line: 12, ...SH.cta('', 'ابعت كلمة «شريك» ونحدد معاك مكالمة الـ 90 دقيقة') },
  ],
};
})();
