/* Ad A — General (Egypt): "مطلوب موظف" job-ad concept. Scene `line` = voice line in scripts/general.txt */
(() => {
const poster = (extra = '') => `<div data-poster style="position:relative;width:900px;background:#fff;border-radius:34px;padding:40px 46px 46px;box-shadow:0 30px 90px rgba(0,0,0,.18);text-align:center;color:var(--ink)">
  <div class="row" style="justify-content:space-between"><span class="chip">وظيفة خالية</span><span style="font-weight:900;font-size:28px;color:#6b7280">تعيين فوري</span></div>
  <div data-m class="poster" style="font-size:230px;color:var(--red);margin-top:16px;opacity:0">مطلوب</div>
  <div data-r class="h1" style="font-size:84px;opacity:0">موظف خدمة عملا</div>
  <div data-d style="margin:26px auto 0;height:0;border-top:5px dashed #d6d0c0;width:0"></div>
  <div data-ap style="opacity:0;margin-top:22px;font-weight:900;font-size:34px;color:#6b7280">التقديم: واتساب فقط</div>
  ${extra}</div>`;
const req = n => `<span class="chip" data-rq style="font-size:36px;padding:6px 30px;opacity:0">الشرط ${n} من 6</span>`;

window.AD = {
  captions: [
    '*مطلوب* موظف خدمة عملا.',
    'الشروط: يشتغل *24* *ساعة…* من غير إجازة، ولا جمعة، ولا عيد.',
    'يرد على أي رسالة *في* *ثواني،* على الواتساب والإنستجرام والفيسبوك… وحتى على *الكومنتات.*',
    'يحفظ كل منتجاتك وأسعارك ومقاساتك… ويسمع *الفويس* *نوت،* ويفهم *الصور.*',
    'ولما العميل يقول *«غالي»…* ما يقولوش «السعر في الخاص». يقنعه، لحد ما *يأكد* *الأوردر.*',
    'يدّي كل عميل *سكور* من 100، ويكتب *ملاحظة* على كل محادثة: جاد ولا بيتفرج… وليه.',
    'وكل أوردر جاهز، ينزّله في *الشيت* *بتاعك:* الاسم، والرقم، والعنوان. انت بس *تشحن.*',
    'المرتب؟ *2,500* *جنيه* في الشهر. *مستحيل؟*',
    'ده مش موظف. ده *OT1-Pro:* مساعد مبيعات بالذكاء الاصطناعي، *مبني* *مخصوص* للبيزنس بتاعك.',
    'مش تمبلت جاهز. بنقعد معاك *90* *دقيقة* نعلّمه منتجاتك وأسلوبك، ونفضل معاك *30* *يوم* نظبطه كل أسبوع. إحنا *شركا،* مش برنامج.',
    'عايز تجربه الأول؟ 14 يوم بـ *3,500* *جنيه…* ولو ما اتعاملش مع 50 محادثة حقيقية، *فلوسك* *ترجعلك* *كاملة.*',
    'وبعدها: *7,500* إعداد + *2,500* في الشهر. وأي حاجة زيادة في دماغك… *بنبنيهالك.*',
    'الوظيفة *اتملت.* دوس على الزرار وابعتلنا على *الواتساب.* OT1-Pro… شريكك في المبيعات، *مش* *شات* *بوت.*',
  ],
  scenes: [
    { line: 0, bg: 'yellow', html: poster(), noWhoosh: true,
      update(el, lt, d) {
        const P = q(el, '[data-poster]'); const k = eo(p(lt, 0, .45)); P.style.transform = `translateY(${(1 - k) * 200}px) rotate(${(1 - k) * -4}deg)`; P.style.opacity = k;
        slam(q(el, '[data-m]'), lt, .35, 0); pop(q(el, '[data-r]'), lt, .8, .35);
        q(el, '[data-d]').style.width = (eo(p(lt, 1.1, .5)) * 100) + '%'; pop(q(el, '[data-ap]'), lt, 1.3, .3);
      },
      sfx: () => [[.35, 'stamp'], [.8, 'pop']] },

    { line: 1, bg: 'yellow', // 24h, no days off
      html: `<div class="col" style="gap:34px;position:relative;width:940px">${req(1)}
        <div class="row" style="gap:26px" data-a><svg width="190" height="190" viewBox="0 0 24 24" fill="none" stroke="#0B0F0E" stroke-width="1.6"><circle cx="12" cy="12" r="10" fill="#fff"/><path data-hand d="M12 12V5.5" stroke-width="2" stroke-linecap="round"/><path data-hand2 d="M12 12l3.5 2" stroke-width="2" stroke-linecap="round"/></svg>
          <div class="poster" style="font-size:190px;line-height:.95">24<br><span style="font-size:110px">ساعة</span></div></div>
        <div class="row" style="gap:18px">${['إجازة', 'جمعة', 'عيد'].map((t, i) => `<div data-o="${i}" class="pill" style="opacity:0;position:relative;font-size:46px;background:#fff;border:none;padding:14px 38px">${t}<i style="position:absolute;left:14px;right:14px;top:52%;height:8px;background:var(--red);border-radius:6px;transform-origin:right;transform:scaleX(0)" data-x></i></div>`).join('')}</div>
        <div data-n class="h2" style="opacity:0">من غير ولا يوم أوف</div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-rq]'), lt, 0, .3); pop(q(el, '[data-a]'), lt, .1);
        q(el, '[data-hand]').setAttribute('transform', `rotate(${lt * 360} 12 12)`); q(el, '[data-hand2]').setAttribute('transform', `rotate(${lt * 30} 12 12)`);
        const s = d * .45; qa(el, '[data-o]').forEach((o, i) => { pop(o, lt, s + i * .35, .3); strike(o.querySelector('[data-x]'), lt, s + i * .35 + .25); });
        pop(q(el, '[data-n]'), lt, d * .82, .35);
      },
      sfx: d => [[.05, 'pop'], ...[0, 1, 2].map(i => [d * .45 + i * .35 + .25, 'tick'])] },

    { line: 2, bg: 'paper', // replies in seconds, everywhere
      html: `<div class="col" style="gap:20px;position:relative;width:940px">${req(2)}
        <div class="row" style="gap:22px"><div class="poster" style="font-size:150px">يرد في</div><div class="serif ltr" data-sw style="font-size:150px;color:var(--em2);line-height:1">0:00</div></div>
        <div class="col" style="gap:14px;width:860px">${[['WhatsApp', 'var(--wa)', 'لسه متاح؟ 🙏'], ['Instagram', '#E1306C', 'عندكم مقاس L؟'], ['Facebook', '#1877F2', 'التوصيل كام يوم؟'], ['كومنت على البوست', 'var(--amber)', 'بكام؟ 🔥']].map((c, i) => `<div data-c="${i}" class="row" style="opacity:0;width:100%;background:#fff;border-radius:24px;padding:14px 22px;gap:18px;box-shadow:0 10px 26px rgba(0,0,0,.08)">
            <i style="width:20px;height:58px;border-radius:10px;background:${c[1]};flex:none"></i><div style="flex:1"><div style="font-weight:800;font-size:22px;color:#6b7280">${c[0]}</div><div style="font-weight:900;font-size:32px">${c[2]}</div></div>
            <span data-ok style="opacity:0;font-weight:900;font-size:28px;color:#fff;background:var(--em);border-radius:999px;padding:4px 18px">✓ اترد</span></div>`).join('')}</div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-rq]'), lt, 0, .3);
        q(el, '[data-sw]').textContent = '0:0' + Math.min(3, Math.floor(Math.max(0, lt - .3) * 2));
        qa(el, '[data-c]').forEach((c, i) => { const a = .5 + i * (d - 1.5) / 4; slideX(c, lt, a, .35, -160); pop(c.querySelector('[data-ok]'), lt, a + .45, .25, 10); });
      },
      sfx: d => [0, 1, 2, 3].flatMap(i => [[.5 + i * (d - 1.5) / 4, 'pop'], [.95 + i * (d - 1.5) / 4, 'tick']]) },

    { line: 3, bg: 'paper', // knows products, hears voice notes, reads photos
      html: `<div class="col" style="gap:24px;position:relative;width:960px">${req(3)}
        <div class="row" style="gap:18px">${[['🧥', 'هودي', '850'], ['👕', 'تيشيرت', '450'], ['👜', 'شنطة', '1,200'], ['👟', 'كوتشي', '1,650']].map((x, i) => `<div data-p="${i}" style="opacity:0;width:215px;background:#fff;border-radius:26px;padding:18px;text-align:center;box-shadow:0 12px 30px rgba(0,0,0,.08)"><div style="font-size:84px;line-height:1.1">${x[0]}</div><div style="font-weight:900;font-size:30px">${x[1]}</div><div class="ltr" style="font-weight:900;font-size:30px;color:var(--em2)">${x[2]} EGP</div></div>`).join('')}</div>
        <div class="row" style="gap:20px;width:100%;justify-content:center">
          <div data-v class="card" style="opacity:0;background:#fff;padding:18px 22px;color:#111"><div style="font-weight:900;font-size:26px;color:#6b7280;margin-bottom:8px">🎤 فويس نوت</div>${C.voice('0:11', 200)}<div data-vt style="opacity:0;font-weight:800;font-size:25px;margin-top:10px">«عايز الهودي ده بس كحلي»</div></div>
          <div data-i class="card" style="opacity:0;background:#fff;padding:18px 22px;color:#111;text-align:center"><div style="font-weight:900;font-size:26px;color:#6b7280;margin-bottom:6px">📷 صورة</div><div style="font-size:96px;line-height:1.1">🧥</div><div data-it style="opacity:0;font-weight:900;font-size:25px;color:var(--em2)">✓ ده موديل 214</div></div>
        </div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-rq]'), lt, 0, .3);
        qa(el, '[data-p]').forEach((c, i) => pop(c, lt, .3 + i * .22, .3));
        pop(q(el, '[data-v]'), lt, d * .5, .35); fade(q(el, '[data-vt]'), lt, d * .5 + .6, .3);
        pop(q(el, '[data-i]'), lt, d * .7, .35); fade(q(el, '[data-it]'), lt, d * .7 + .5, .3);
      },
      sfx: d => [[.3, 'pop'], [.52, 'pop'], [.74, 'pop'], [.96, 'pop'], [d * .5, 'pop'], [d * .7, 'pop']] },

    { line: 4, bg: 'yellow', // handles "expensive" and closes
      html: `<div class="row" style="gap:26px;position:relative">
        <div class="col" style="gap:20px;width:330px">
          <div style="font-weight:900;font-size:34px">موظف عادي:</div>
          <div data-bad style="opacity:0;position:relative;background:#fff;border-radius:22px;padding:14px 18px;font-weight:900;font-size:30px;color:#111">السعر في الخاص<i data-x style="position:absolute;left:-6px;right:-6px;top:50%;height:8px;background:var(--red);border-radius:6px;transform-origin:right;transform:scaleX(0)"></i></div>
          <div style="font-weight:900;font-size:34px;margin-top:20px">OT1-Pro:</div>
          <div data-good style="opacity:0;font-weight:900;font-size:40px;color:var(--em2);background:#fff;border-radius:22px;padding:10px 18px">بيقنع 💬 ويقفل ✓</div>
        </div>
        <div style="position:relative">${C.phone('محمود', 'WhatsApp', [
          ['in', 'الهودي الأسود متاح XL؟ بكام؟'],
          ['out', 'متاح يا محمود 👌 سعره 850 والشحن يومين'],
          ['in', 'غالي شوية 😅'],
          ['out', 'قطن تقيل ومش بيكش، ولو أخدت اتنين الشحن مجاني. أحجزلك؟'],
          ['in', 'خلاص احجزلي اتنين 👍'],
        ])}<div data-st class="stamp" style="color:var(--em2);font-size:72px;bottom:60px;left:50%;background:rgba(255,255,255,.92)">أوردر مؤكد ✓</div></div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-bad]'), lt, .2); strike(q(el, '[data-x]'), lt, .7);
        const T = [.4, d * .2, d * .4, d * .55, d * .74]; chatSeq(el, lt, T);
        pop(q(el, '[data-good]'), lt, d * .55, .35);
        const st = q(el, '[data-st]'); const k = p(lt, d * .84, .22); st.style.opacity = k; st.style.transform = `translateX(-50%) rotate(-8deg) scale(${2.2 - 1.2 * eo(k)})`;
      },
      sfx: d => [[.7, 'tick'], [.4, 'pop'], [d * .2, 'pop'], [d * .4, 'pop'], [d * .55, 'pop'], [d * .74, 'pop'], [d * .84, 'stamp'], [d * .86, 'ding']] },

    { line: 5, bg: 'yellow', // score + AI note per conversation
      html: `<div class="col" style="gap:20px;position:relative;width:940px">${req(5)}
        <div class="h2" data-h style="font-size:56px">سكور + ملاحظة على كل محادثة</div>
        ${C.scoreCard('محمود ع.', 94, 'سأل عن المقاس والشحن وبعت عنوانه. جاهز يطلب.', '#00B67A')}
        ${C.scoreCard('كريم س.', 61, 'عجبه الموديل، متردد بسبب السعر.', '#F59E0B')}
        ${C.scoreCard('زياد م.', 12, 'بيتفرج بس، ما سألش عن سعر ولا مقاس.', '#9CA3AF')}</div>`,
      update(el, lt, d) { pop(q(el, '[data-rq]'), lt, 0, .3); pop(q(el, '[data-h]'), lt, .15); qa(el, '[data-sc]').forEach((c, i) => animScore(c, lt, .6 + i * (d - 1.6) / 3)); },
      sfx: d => [0, 1, 2].map(i => [.6 + i * (d - 1.6) / 3, 'pop']) },

    { line: 6, bg: 'paper', // orders into your sheet
      html: `<div class="col" style="gap:28px;position:relative">${req(6)}${C.sheet('أوردرات جاهزة · Google Sheet', ['الاسم', 'الرقم', 'العنوان', 'الطلب'], [
        ['محمود ع.', '010•••4417', 'مدينة نصر', '2 هودي أسود XL'], ['كريم س.', '011•••0923', 'سموحة', 'تيشيرت L'],
        ['أحمد ف.', '012•••5560', 'المنصورة', 'شنطة جلد'], ['حسن ي.', '015•••2209', 'طنطا', '3 تيشيرت']])}
        <div data-ship class="pill" style="opacity:0;font-size:52px;background:var(--ink);color:var(--yellow);border:none;padding:16px 44px">📦 انت بس تشحن</div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-rq]'), lt, 0, .3); pop(q(el, '.sheet'), lt, .1);
        qa(el, 'tbody tr').forEach((r, i) => { const a = .6 + i * (d * .5) / 4, k = eo(p(lt, a, .3)); r.style.opacity = k; r.style.transform = `translateX(${(1 - k) * -80}px)`; qa(r, 'td').forEach(td => td.style.background = lt - a < 1 ? '#d9f6e8' : '#fff'); });
        pop(q(el, '[data-ship]'), lt, d * .78, .35);
      },
      sfx: d => [0, 1, 2, 3].map(i => [.6 + i * (d * .5) / 4, 'pop']).concat([[d * .78, 'ding']]) },

    { line: 7, bg: 'ink', // salary punchline
      html: `<div class="col" style="gap:20px;position:relative">
        <div data-q class="poster" style="font-size:150px;color:var(--yellow);opacity:0">المرتب؟</div>
        <div data-s class="row" style="gap:20px;opacity:0"><span class="serif ltr" style="font-size:230px;line-height:1">2,500</span><span style="font-weight:900;font-size:60px;line-height:1.1">جنيه<br>في الشهر</span></div>
        <div data-st class="stamp" style="color:var(--red);font-size:150px;top:470px;left:50%;background:rgba(11,15,14,.85)">مستحيل؟</div></div>`,
      update(el, lt, d) {
        pop(q(el, '[data-q]'), lt, .05, .3); pop(q(el, '[data-s]'), lt, d * .3, .35);
        const st = q(el, '[data-st]'), k = p(lt, d * .74, .22); st.style.opacity = k; st.style.transform = `translateX(-50%) rotate(-7deg) scale(${2.4 - 1.4 * eo(k)})`;
      },
      sfx: d => [[.05, 'pop'], [d * .3, 'ding'], [d * .74, 'stamp']] },

    { line: 8, bg: 'em', // reveal
      html: `<div class="col" style="gap:26px">
        <div data-a class="h1" style="font-size:96px">${W('ده مش موظف.')}</div>
        <div data-l class="serif ltr" style="font-size:210px;line-height:1;opacity:0">OT1-Pro</div>
        <div data-b class="h2" style="font-size:52px;opacity:0">مساعد مبيعات بالذكاء الاصطناعي<br><span style="background:var(--yellow);color:var(--ink);border-radius:14px;padding:0 18px">مبني مخصوص للبيزنس بتاعك</span></div></div>`,
      update(el, lt, d) {
        words(q(el, '[data-a]'), lt, 0, .2);
        const l = q(el, '[data-l]'), k = eo(p(lt, d * .3, .5)); l.style.opacity = k; l.style.letterSpacing = `${(1 - k) * 40}px`; l.style.transform = `scale(${.8 + .2 * eb(p(lt, d * .3, .5))})`;
        pop(q(el, '[data-b]'), lt, d * .55, .4);
      },
      sfx: d => [[d * .3 - 1.2 > 0 ? d * .3 - 1.2 : 0, 'rise'], [d * .3, 'ding']] },

    { line: 9, ...SH.partner('إحنا <span class="em">شركا</span>، مش برنامج') },
    { line: 10, ...SH.pricing({ line: 11 }, '+ أي حاجة زيادة في دماغك… بنبنيهالك') },

    { line: 12, bg: 'yellow', ctaAt: 1.6,
      html: '', topAnim: null },
  ],
};

/* last scene: the job poster comes back stamped "اتملت", then the CTA */
const cta = SH.cta(poster(`<div data-st class="stamp" style="color:var(--em2);font-size:120px;top:120px;left:50%;background:rgba(255,255,255,.9)">اتملت ✓</div>`), 'ابعت كلمة «شريك» ونحدد معاك مكالمة الـ 90 دقيقة');
const last = window.AD.scenes[window.AD.scenes.length - 1];
Object.assign(last, cta, { bg: 'yellow', ctaAt: 1.6,
  topAnim(top, lt) {
    const P = q(top, '[data-poster]'); P.style.opacity = 1 - p(lt, 1.3, .3); P.style.transform = `scale(${.62}) translateY(${-p(lt, 1.3, .3) * 200}px)`;
    top.style.height = (1 - eo(p(lt, 1.3, .3))) * 520 + 'px'; top.style.marginBottom = (1 - eo(p(lt, 1.3, .3))) * -120 + 'px';
    qa(P, '[data-m],[data-r],[data-ap]').forEach(e => e.style.opacity = 1); q(P, '[data-d]').style.width = '100%';
    const st = q(P, '[data-st]'), k = p(lt, .2, .22); st.style.opacity = k; st.style.transform = `translateX(-50%) rotate(-10deg) scale(${2.2 - 1.2 * eo(k)})`;
  },
  sfx: () => [[.2, 'stamp'], [1.6, 'whoosh'], [2.2, 'pop']] });
// CTA scene on yellow needs dark text for the logo lines
last.html = cta.html.replace('color:#d1fae5', 'color:#3f3f2f').replace('<span style="color:var(--yellow)">مش شات بوت</span>', '<span style="color:var(--red)">مش شات بوت</span>');
})();
