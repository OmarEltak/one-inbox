/* Ad A — General small business (Egypt). Scene i starts on voice line `line` (see scripts/general.txt). */
(() => {
const wave = (n, w) => '<svg width="' + w + '" height="40" viewBox="0 0 ' + w + ' 40">' + Array.from({ length: n }, (_, i) => { const h = 6 + 14 * Math.abs(Math.sin(i * 1.7)); return `<rect x="${i * (w / n)}" y="${20 - h}" width="${w / n * .55}" rx="1.5" height="${2 * h}" fill="#9db5ae"/>`; }).join('') + '</svg>';
const leads = [['محمود ع.', 'سأل عن المقاس والشحن', 93, 'hot', 'سخن'], ['كريم س.', 'طلب صور تانية', 71, 'warm', 'دافي'], ['أحمد ف.', 'رجع تاني بعد يومين', 66, 'warm', 'دافي'], ['زياد م.', '«بتفرج بس»', 14, 'cold', 'بارد']];

window.AD = {
  captions: [
    'الساعة 2 بالليل… حد بعتلك على الواتساب: «لسه متاح؟ عايز أطلب.» وانت *نايم.*',
    'الصبح ترد… تلاقيه *اشترى* *من* *غيرك.*',
    'وعشان تلحق كل رسالة، محتاج فريق موديريتورز: *ورديات،* *ومرتبات،* *وتدريب…* ولسه برضه في رسايل *بتضيع.*',
    'عشان كده عملنا *OT1-Pro.* ومش شات بوت بيقول «تم استلام رسالتك».',
    'ده *مساعد* *مبيعات* بالذكاء الاصطناعي، شغال *24* *ساعة،* على الواتساب والإنستجرام والفيسبوك.',
    'بيرد على الأسعار والمقاسات والمتاح، بيفهم العميل عايز إيه، ولو قال *«غالي»…* بيعرف يرد، ويوصّله لحد ما *يأكد* *الأوردر.*',
    'بيسمع *الفويس* *نوت،* وبيفهم *الصور* والسكرين شوتس.',
    'بيرد على *كومنتات* البوستات، ويحوّل المهتمين *للخاص.*',
    'بيقولك مين العميل *الجاد* ومين بيتفرج، عشان تركز على اللي *هيشتري.*',
    'وبيوريك أكتر حاجة *بتتطلب،* وليه الناس *بتتردد* قبل ما تشتري.',
    'وأحلى حاجة… الأوردرات الجاهزة بتنزل لوحدها في *الشيت* *بتاعك:* الاسم، والرقم، والعنوان، والطلب. انت بس *تشحن.*',
    'وإحنا مش هنبيعلك برنامج ونمشي. إحنا *شركا.* بنقعد معاك *90* *دقيقة* نبني الذكاء الاصطناعي بأسلوب البيزنس بتاعك، ونفضل معاك *30* *يوم* نظبطه كل أسبوع.',
    'عايز تجرب الأول؟ التجربة 14 يوم بـ *3,500* *جنيه.* ولو ما اتعاملش مع 50 محادثة حقيقية… *فلوسك* *بترجعلك* *كاملة.*',
    'وباقة الشريك المؤسس: *7,500* إعداد + *2,500* في الشهر… أقل من نص مرتب موظف واحد، وشغال *ليل* *ونهار.*',
    'عايز ربط بمتجرك؟ بالسيستم بتاعك؟ كذا براند؟ أي حاجة في دماغك… *بنبنيهالك.*',
    'دوس على الزرار، وابعتلنا على *الواتساب.* OT1-Pro… شريكك في المبيعات، *مش* *شات* *بوت.*',
  ],
  scenes: [
    { line: 0, // 2 AM lock screen, you're asleep
      html: `<div style="position:relative">${C.lock('02:07', 'الجمعة', 'WhatsApp · محمود', 'لسه متاح؟ عايز أطلب')}
        <div data-z style="position:absolute;top:560px;left:0;right:0;text-align:center;font-family:Serif;font-size:90px;color:var(--muted);opacity:0;direction:ltr">z z z</div></div>`,
      update(el, lt, d) { pop(q(el, '.notif'), lt, .5, .45); const z = q(el, '[data-z]'); z.style.opacity = p(lt, d * .7, .5) * (.6 + .4 * Math.sin(lt * 3)); z.style.transform = `translateY(${-10 * Math.sin(lt * 2)}px)`; } },
    { line: 1, // morning: too late
      html: C.phone('محمود', 'آخر ظهور 2:09 ص', [
        ['in', 'لسه متاح؟ عايز أطلب<span class="meta ltr">02:07</span>'],
        ['out', 'صباح الخير، متاح يا فندم<span class="meta ltr">09:41</span>', false],
        ['in', 'خلاص يا باشا، طلبته من مكان تاني<span class="meta ltr">09:52</span>'],
      ]),
      update(el, lt, d) { chatSeq(el, lt, [0, .5, d * .5]); } },
    { line: 2, // moderator team: shifts, salaries, training, still lost
      html: `<div class="col" style="gap:22px;width:900px">
        <div class="row" style="gap:16px;width:100%">${[['وردية الصبح', '9 ص – 5 م'], ['وردية بالليل', '5 م – 1 ص'], ['وردية الفجر', '1 ص – 9 ص']].map((s, i) => `<div data-s="${i}" class="card" style="flex:1;text-align:center;opacity:0;padding:22px 12px">
          <svg width="70" height="70" viewBox="0 0 24 24" fill="none" stroke="#9db5ae" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
          <div style="font-weight:900;font-size:30px">${s[0]}</div><div class="muted ltr" style="font-weight:700;font-size:24px">${s[1]}</div></div>`).join('')}</div>
        <div class="row" style="gap:14px">${['مرتبات', 'تدريب', 'إجازات', 'غياب'].map((t, i) => `<div data-c="${i}" class="pill" style="opacity:0;font-size:30px">+ ${t}</div>`).join('')}</div>
        <div data-u class="card" style="opacity:0;width:100%;display:flex;align-items:center;justify-content:space-between;border-color:var(--red)">
          <div style="font-weight:900;font-size:40px">رسايل ما اتردش عليها</div><div class="serif red ltr" data-n style="font-size:110px;line-height:1">0</div></div></div>`,
      update(el, lt, d) {
        qa(el, '[data-s]').forEach((s, i) => pop(s, lt, i * .35, .4));
        qa(el, '[data-c]').forEach((c, i) => pop(c, lt, 1.4 + i * .3, .35, 24));
        pop(q(el, '[data-u]'), lt, d * .62, .4); q(el, '[data-n]').textContent = Math.round(47 * eo(p(lt, d * .62, d * .38)));
      } },
    { line: 3, // brand reveal, not a chatbot
      html: `<div class="col" style="gap:40px">
        <div data-l class="serif" style="font-size:190px;line-height:1">OT1-Pro</div>
        <div data-b style="position:relative;opacity:0"><div class="b in" style="opacity:1;font-size:34px;max-width:none;padding:22px 30px">تم استلام رسالتك، سيتم الرد عليك في أقرب وقت</div>
          <i data-x style="position:absolute;left:-10px;right:-10px;top:50%;height:10px;background:var(--red);border-radius:6px;transform-origin:right;transform:scaleX(0)"></i></div>
        <div data-t class="h2" style="opacity:0">مش <span class="red">شات بوت</span></div></div>`,
      update(el, lt, d) {
        const l = q(el, '[data-l]'); const k = eo(p(lt, 0, .7)); l.style.opacity = k; l.style.letterSpacing = `${(1 - k) * 30}px`;
        pop(q(el, '[data-b]'), lt, d * .45, .4); q(el, '[data-x]').style.transform = `scaleX(${eo(p(lt, d * .62, .3))})`; pop(q(el, '[data-t]'), lt, d * .7, .35);
      } },
    { line: 4, // AI sales assistant, 24/7, channels
      html: `<div class="col" style="gap:30px">
        <div data-a class="h1">مساعد <span class="mint">مبيعات</span><br>بالذكاء الاصطناعي</div>
        <div data-c class="row" style="gap:22px;opacity:0"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path data-hand d="M12 12 12 6" stroke-linecap="round"/><path d="M12 12l4 2" stroke-linecap="round"/></svg>
          <div><div class="serif ltr" style="font-size:130px;line-height:1">24/7</div></div></div>
        <div class="row" style="gap:16px">${[['واتساب', 'var(--wa)'], ['إنستجرام', 'var(--ig)'], ['فيسبوك', 'var(--fb)']].map((c, i) => `<div data-p="${i}" class="pill" style="opacity:0;font-size:38px"><i style="width:20px;height:20px;border-radius:50%;background:${c[1]};display:inline-block"></i>${c[0]}</div>`).join('')}</div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-a]'), lt, 0); pop(q(el, '[data-c]'), lt, d * .3, .4);
        q(el, '[data-hand]').setAttribute('transform', `rotate(${lt * 120} 12 12)`);
        qa(el, '[data-p]').forEach((c, i) => pop(c, lt, d * .55 + i * .35, .35, 30));
      } },
    { line: 5, // sells + handles "expensive" + closes
      html: `<div style="position:relative">${C.phone('محمود', 'WhatsApp', [
        ['in', 'الهودي الأسود متاح XL؟ بكام؟'],
        ['out', 'متاح XL يا محمود، سعره 850 جنيه والشحن يومين'],
        ['in', 'غالي شوية'],
        ['out', 'قطن تقيل ومش بيكش. ولو أخدت اتنين الشحن عليا. أحجزلك؟'],
        ['in', 'تمام احجزلي اتنين'],
      ], { example: true })}
        <div data-ok class="pill" style="position:absolute;bottom:-26px;left:50%;transform:translateX(-50%);white-space:nowrap;opacity:0;background:var(--em);color:var(--ink);border-color:var(--em);font-size:36px">✓ أوردر مؤكد</div></div>`,
      update(el, lt, d) { chatSeq(el, lt, [.1, d * .18, d * .42, d * .55, d * .78]); pop(q(el, '[data-ok]'), lt, d * .86, .35, 20); } },
    { line: 6, // voice notes + images
      html: C.phone('كريم', 'Instagram', [
        ['in', `<span class="row" style="gap:12px"><b style="width:44px;height:44px;border-radius:50%;background:var(--mint);display:inline-flex;align-items:center;justify-content:center;color:var(--ink)">▶</b>${wave(28, 180)}<span class="muted ltr" style="font-size:20px">0:09</span></span>`],
        ['in', `<span style="display:block;width:250px;height:170px;border-radius:16px;background:linear-gradient(135deg,#24443c,#183530);position:relative;overflow:hidden"><svg style="position:absolute;inset:0;margin:auto" width="120" height="120" viewBox="0 0 24 24" fill="#9db5ae"><path d="M8 3 4 6l2 4 2-1v12h8V9l2 1 2-4-4-3c-.5 1.7-2 3-4 3S8.5 4.7 8 3z"/></svg></span>`],
        ['out', 'سمعتك يا كريم. ده موديل 214، متاح بالكحلي مقاس L و XL'],
      ], { example: true }),
      update(el, lt, d) { chatSeq(el, lt, [.1, d * .3, d * .6]); } },
    { line: 7, // comments -> DM
      html: `<div class="col" style="gap:20px;width:880px">
        <div class="card" style="width:100%;padding:22px 26px">
          <div class="row" style="gap:14px"><div style="width:58px;height:58px;border-radius:50%;background:var(--em);display:flex;align-items:center;justify-content:center;font-family:Serif;font-size:26px;color:var(--ink)">B</div><div style="font-weight:800;font-size:28px">براندك · بوست جديد</div></div>
          <div style="height:170px;border-radius:18px;margin:16px 0;background:linear-gradient(135deg,#183530,#0f231e);border:2px solid var(--line);display:flex;align-items:center;justify-content:center;gap:40px"><svg width="120" height="120" viewBox="0 0 24 24" fill="#9db5ae"><path d="M8 3 4 6l2 4 2-1v12h8V9l2 1 2-4-4-3c-.5 1.7-2 3-4 3S8.5 4.7 8 3z"/></svg><div style="font-weight:900;font-size:40px">كوليكشن الشتا<br><span class="mint">نزل النهارده</span></div></div>
          ${[['عمر', 'بكام؟'], ['حسن', 'السعر لو سمحت'], ['مازن', 'فيه توصيل إسكندرية؟']].map((c, i) => `<div data-c="${i}" style="opacity:0;margin-top:12px">
            <div style="font-weight:700;font-size:28px"><b>${c[0]}</b> <span class="soft">${c[1]}</span></div>
            <div data-r style="opacity:0;font-weight:700;font-size:25px;color:var(--mint);margin-inline-start:30px">↳ رد AI: بعتلك التفاصيل في الخاص</div></div>`).join('')}
        </div>
        <div data-dm class="pill" style="opacity:0;font-size:36px"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg> 3 محادثات جديدة في الخاص</div></div>`,
      update(el, lt, d) {
        qa(el, '[data-c]').forEach((c, i) => { pop(c, lt, .2 + i * .45, .35, 20); fade(c.querySelector('[data-r]'), lt, d * .45 + i * .3, .3); });
        pop(q(el, '[data-dm]'), lt, d * .75, .35);
      } },
    { line: 8, // lead scoring
      html: `<div class="col" style="gap:16px;width:900px">${leads.map((l, i) => `<div data-l="${i}" class="lead" style="opacity:0">
          <div class="nm">${l[0]}<div class="muted" style="font-size:24px;font-weight:700">${l[1]}</div></div>
          <div class="sc ${l[3] === 'hot' ? 'red' : l[3] === 'warm' ? 'amber' : ''}" data-v="${l[2]}">0</div><span class="badge ${l[3]}">${l[4]}</span></div>`).join('')}
        <div data-f class="pill" style="opacity:0;font-size:36px;border-color:var(--red);color:var(--red)">كلّم دول الأول</div></div>`,
      update(el, lt, d) {
        qa(el, '[data-l]').forEach((l, i) => { slideX(l, lt, .1 + i * .3, .35, -70); const v = l.querySelector('[data-v]'); v.textContent = Math.round(+v.dataset.v * eo(p(lt, .3 + i * .3, .8))); });
        const k = p(lt, d * .6, .3); const hot = q(el, '[data-l="0"]'); hot.style.borderColor = k > .5 ? 'var(--red)' : ''; hot.style.boxShadow = k > .5 ? '0 0 40px rgba(251,113,133,.25)' : '';
        pop(q(el, '[data-f]'), lt, d * .6, .35);
      } },
    { line: 9, // insights
      html: `<div class="col" style="gap:22px;width:920px;position:relative">
        <div class="example" style="top:-30px">مثال توضيحي</div>
        <div data-a style="width:100%">${SH.bars('أكتر حاجة بتتطلب', [['هودي أسود', 38], ['تيشيرت أوفر', 27], ['شنطة جلد', 19]])}</div>
        <div data-b style="width:100%;opacity:0">${SH.bars('ليه الناس بتتردد؟', [['السعر', 41], ['مصاريف الشحن', 33], ['المقاس', 18]], 'var(--amber)')}</div></div>`,
      update(el, lt, d) { pop(q(el, '[data-a]'), lt, 0); SH.growBars(q(el, '[data-a]'), lt, .3); pop(q(el, '[data-b]'), lt, d * .45, .4); SH.growBars(q(el, '[data-b]'), lt, d * .45 + .3); } },
    { line: 10, // orders land in your sheet
      html: `<div class="col" style="gap:30px">${C.sheet('أوردرات جاهزة · Google Sheet', ['الاسم', 'الرقم', 'العنوان', 'الطلب'], [
        ['محمود ع.', '010•••4417', 'مدينة نصر', '2 هودي أسود XL'], ['كريم س.', '011•••0923', 'سموحة', 'تيشيرت أوفر L'],
        ['أحمد ف.', '012•••5560', 'المنصورة', 'شنطة جلد بني'], ['عمر ح.', '010•••7781', 'المعادي', 'هودي كحلي M'],
        ['حسن ي.', '015•••2209', 'طنطا', '3 تيشيرت']])}
        <div data-ship class="pill" style="opacity:0;font-size:44px;background:var(--em);color:var(--ink);border-color:var(--em)">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#0a1814" stroke-width="2"><path d="M21 8 12 3 3 8v8l9 5 9-5zM3 8l9 5 9-5M12 13v8"/></svg> انت بس تشحن</div></div>`,
      update(el, lt, d) {
        pop(q(el, '.sheet'), lt, 0);
        qa(el, 'tbody tr').forEach((r, i) => { const a = d * .2 + i * (d * .5) / 5; const k = eo(p(lt, a, .35)); r.style.opacity = k; r.style.transform = `translateX(${(1 - k) * -60}px)`; qa(r, 'td').forEach(td => td.style.background = lt - a < 1.2 ? '#d9f6e8' : '#fff'); });
        pop(q(el, '[data-ship]'), lt, d * .82, .35);
      } },
    { line: 11, ...SH.partner('إحنا <span class="mint">شركا</span>، مش برنامج ونمشي') },
    { line: 12, ...SH.pricing({ line: 13 }, 'شغال 24 ساعة · أقل من نص مرتب موظف واحد') },
    { line: 14, ...SH.custom('عايز حاجة مخصوص؟', ['ربط بمتجرك Shopify / WooCommerce', 'ربط بالـ CRM', 'كذا براند', 'ودجت لموقعك']) },
    { line: 15, ...SH.cta('ابعت كلمة «شريك» ونحدد معاك مكالمة الـ 90 دقيقة') },
  ],
};
})();
