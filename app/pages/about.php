<?php
// About page. Wording lives here (English and Kiswahili); partners and helplines come from app/data.php.
// Kiswahili needs review by a native speaker. Do not add claims here that the system does not enforce.
if (!lang()) set_lang('en');
$A = ['en' => [
  'title'=>'About Bomaveda', 'kick'=>'About us', 'h1'=>'Early care for young people in Kilifi County',
  'lead'=>'Bomaveda turns early warning signs in the community into early, protective care. It is a safe, anonymous way to tell someone who can help, before harm takes root.',
  'why'=>'Why we exist',
  'why.t'=>'Families, young people and neighbours often notice the first signs long before a crisis: sudden isolation, emotional distress, dropping out of school, or someone being pulled towards a gang or an extremist group. Bomaveda connects a young person to mentorship and support at that early stage.',
  'pr'=>[['Care over criminalisation','Concerns go to trusted local mentors, counsellors and community liaisons, not to the police. Bomaveda is not a police or surveillance system.'],
         ['Built for shared phones','Reports can be made by USSD on any basic phone, or on the web app. Nothing is sent back to your phone, so nothing is left on a shared handset.'],
         ['Open to everyone','Available in English and Kiswahili, with short steps and plain words, so nobody is locked out by language, literacy or the phone they own.']],
  'ussd'=>'USSD code', 'ussd.t'=>'Dial this on any phone to report or check a case. It works without mobile data.',
  'mv'=>'Mission and vision', 'mission'=>'Our mission', 'mission.t'=>'To give caregivers, young people and community members safe, anonymous and easy ways to report early warning signs, and to turn those reports into timely protective action: linking young people to trusted mentors before radicalisation, dropping out of school or violence become hard to reverse.',
  'vision'=>'Our vision', 'vision.q'=>'Belong. Build. Protect.', 'vision.t'=>'A coastal Kenya where every young person at risk is met with mentorship, psychological care and economic opportunity, and where every community can protect its young people without fear of surveillance or retaliation.',
  'fv'=>'What we focus on and what we value', 'focus'=>'Focus areas',
  'f'=>['Radicalisation and extremist recruitment','Isolation and emotional distress in young people','School dropout and vulnerability','Gang involvement and youth crime','Child protection and abuse','Community and land conflict'],
  'values'=>'Values', 'v'=>['Safety first','Uncompromising anonymity','Care over criminalisation','Grassroots trust','Radical inclusion','Early prevention'],
  'who'=>'Who Bomaveda is for', 'who1'=>'Who can use it',
  'w1'=>['Mothers and caregivers who notice sudden changes, distress or isolation in a child','Young people and peers who want quiet support, or are worried about a friend being targeted by recruiters','Young people at risk of dropping out of school, joining a gang or facing a crisis','Neighbours, teachers and religious leaders who see a young person at risk in their ward'],
  'who2'=>'Who looks after reports',
  'w2'=>['Trained community liaisons and social workers from partner organisations, who review cases discreetly','County social welfare officers and partner organisations, who see only anonymised, ward-level counts to plan services'],
  'net'=>'Our referral network', 'net.t'=>'When a case is verified, Bomaveda can link it to specialist partners without exposing the person who reported.',
  'th'=>['Area','Partner','What they do'],
  'rows'=>[['County policy and social care','Kilifi County (FUND)','Uses anonymised ward trends to guide social welfare, bursaries and youth programmes.'],
           ['Rights protection and verification','Haki Africa and KECOSCE (CARRY)','Vetted rights liaisons who verify alerts and coordinate community-level protection.'],
           ['Community mentorship','Coast Education Centre (CARRY)','Connects young people to learning support, vocational training and mentors.'],
           ['Youth outreach','Sauti ya Vijana (CONNECT)','A grassroots peer network leading youth engagement and reducing stigma in local barazas.'],
           ['Child protection','Child helpline 116','For child abuse, exploitation or a child who is not safe.'],
           ['Violence and trauma support','Violence help line 1195','For gender-based violence or trauma needing medical or legal support.']],
  'how'=>'How reports are handled',
  'h'=>['Every report goes through seven stages, from received to resolved, and you can see the stage with your case number.','You follow up with your 12-digit case number. We do not send texts or call back unless you chose to give a contact and said it is safe to call.','Staff see only the cases they are allowed to see, every time a contact is revealed it is recorded, and a referral needs two different people to agree.','Managers see counts only, and small numbers are hidden so nobody can be picked out.','If a young person is in immediate danger, we may involve the authorities and share only what is needed.'],
  'law'=>'Rights and the law', 'law.t'=>'Bomaveda is designed around the Constitution of Kenya (2010) and the Data Protection Act (2019).',
  'art'=>[['Article 27: Equality','Equal access to community protection, whatever your status or phone.'],['Article 28: Dignity','Protects young people and reporters from humiliating exposure.'],['Article 29: Freedom and security of the person','Protection from violence, intimidation and recruitment.'],['Article 43: Economic and social rights','The right to social care, health and basic well-being.']],
  'dp'=>'Data protection in practice',
  'd'=>['Encrypted: report text and any contact details are encrypted before they are stored.','No phone number or IP address saved: USSD never reads the caller number, and the web app does not store your address.','Consent by design: clear, plain-language choices before you send anything. Contact details are always optional.','Anonymity built into the system, not only promised in words.'],
  'cta'=>'Ready to talk to someone?', 'cta.t'=>'You do not need to give your name.'
], 'sw' => [
  'title'=>'Kuhusu Bomaveda', 'kick'=>'Kuhusu sisi', 'h1'=>'Malezi ya mapema kwa vijana wa Kaunti ya Kilifi',
  'lead'=>'Bomaveda hugeuza dalili za mapema katika jamii kuwa huduma ya mapema ya ulinzi. Ni njia salama na ya siri ya kumwambia mtu anayeweza kusaidia, kabla madhara hayajaota mizizi.',
  'why'=>'Kwa nini tupo',
  'why.t'=>'Familia, vijana na majirani mara nyingi huona dalili za kwanza muda mrefu kabla ya msiba: kujitenga ghafla, huzuni kubwa, kuacha shule, au mtu kuvutwa kwenye genge au kundi lenye itikadi kali. Bomaveda huunganisha kijana na ushauri na msaada katika hatua hiyo ya mapema.',
  'pr'=>[['Malezi badala ya adhabu','Wasiwasi hupelekwa kwa washauri, wanasaha na wawakilishi wa jamii wanaoaminika, si polisi. Bomaveda si mfumo wa polisi wala wa kuchunguza watu.'],
         ['Imeundwa kwa simu za kushiriki','Unaweza kuripoti kwa USSD kwenye simu yoyote ya kawaida, au kwenye programu ya wavuti. Hakuna kinachotumwa kwenye simu yako, kwa hivyo hakuna alama kwenye simu ya pamoja.'],
         ['Wazi kwa kila mtu','Inapatikana kwa Kiswahili na Kiingereza, kwa hatua fupi na maneno rahisi, ili hakuna anayekosa kwa sababu ya lugha, kusoma au simu anayomiliki.']],
  'ussd'=>'Namba ya USSD', 'ussd.t'=>'Piga namba hii kwenye simu yoyote kuripoti au kuangalia kesi. Haihitaji data ya simu.',
  'mv'=>'Dhamira na maono', 'mission'=>'Dhamira yetu', 'mission.t'=>'Kuwapa walezi, vijana na wanajamii njia salama, za siri na rahisi za kuripoti dalili za mapema, na kugeuza ripoti hizo kuwa hatua za ulinzi kwa wakati: kuunganisha vijana na washauri wanaoaminika kabla itikadi kali, kuacha shule au vurugu hazijawa vigumu kurekebisha.',
  'vision'=>'Maono yetu', 'vision.q'=>'Belong. Build. Protect.', 'vision.t'=>'Pwani ya Kenya ambapo kila kijana aliye hatarini anapata ushauri, huduma ya kisaikolojia na fursa za kiuchumi, na ambapo kila jamii inaweza kulinda vijana wake bila hofu ya kuchunguzwa au kulipiziwa kisasi.',
  'fv'=>'Tunachozingatia na tunachothamini', 'focus'=>'Maeneo tunayozingatia',
  'f'=>['Itikadi kali na kuandikishwa na makundi ya kigaidi','Kujitenga na huzuni kwa vijana','Kuacha shule na hali ya hatari','Kujihusisha na magenge na uhalifu wa vijana','Ulinzi wa watoto na unyanyasaji','Migogoro ya jamii na ardhi'],
  'values'=>'Maadili', 'v'=>['Usalama kwanza','Usiri usioyumba','Malezi badala ya adhabu','Imani ya jamii','Ujumuishaji kamili','Kuzuia mapema'],
  'who'=>'Bomaveda ni ya nani', 'who1'=>'Nani anaweza kutumia',
  'w1'=>['Mama na walezi wanaoona mabadiliko ya ghafla, huzuni au kujitenga kwa mtoto','Vijana na rika wanaotaka msaada wa siri, au wanaohofia rafiki anayelengwa na waandikishaji','Vijana walio hatarini kuacha shule, kujiunga na genge au kukumbwa na msongo mkubwa','Majirani, walimu na viongozi wa dini wanaoona kijana aliye hatarini kwenye wadi yao'],
  'who2'=>'Nani anashughulikia ripoti',
  'w2'=>['Wawakilishi wa jamii waliofunzwa na wafanyakazi wa kijamii wa mashirika washirika, wanaokagua kesi kwa siri','Maafisa wa ustawi wa jamii wa kaunti na mashirika washirika, wanaoona hesabu zisizotambulisha mtu, kwa kila wadi, ili kupanga huduma'],
  'net'=>'Mtandao wetu wa rufaa', 'net.t'=>'Kesi ikithibitishwa, Bomaveda inaweza kuiunganisha na washirika wataalamu bila kumfichua aliyeripoti.',
  'th'=>['Eneo','Mshirika','Wanachofanya'],
  'rows'=>[['Sera za kaunti na huduma za jamii','Kaunti ya Kilifi (FUND)','Hutumia mwelekeo usiotambulisha mtu kwa kila wadi kuongoza ustawi wa jamii, bursari na programu za vijana.'],
           ['Ulinzi wa haki na uthibitisho','Haki Africa na KECOSCE (CARRY)','Wawakilishi wa haki waliokaguliwa wanaothibitisha taarifa na kuratibu ulinzi wa jamii.'],
           ['Ushauri wa jamii','Coast Education Centre (CARRY)','Huunganisha vijana na msaada wa masomo, mafunzo ya ufundi na washauri.'],
           ['Uhamasishaji wa vijana','Sauti ya Vijana (CONNECT)','Mtandao wa rika unaoongoza ushiriki wa vijana na kupunguza unyanyapaa kwenye mabaraza.'],
           ['Ulinzi wa mtoto','Msaada wa watoto 116','Kwa unyanyasaji wa mtoto, unyonyaji au mtoto asiye salama.'],
           ['Msaada wa ukatili na msongo','Msaada wa ukatili 1195','Kwa ukatili wa kijinsia au msongo unaohitaji msaada wa matibabu au wa kisheria.']],
  'how'=>'Jinsi ripoti zinavyoshughulikiwa',
  'h'=>['Kila ripoti hupitia hatua saba, kuanzia kupokelewa hadi kutatuliwa, na unaona hatua kwa nambari ya kesi yako.','Unafuatilia kwa nambari ya kesi yenye tarakimu 12. Hatutumi ujumbe wala kupiga simu isipokuwa uliacha mawasiliano na ukasema ni salama kupiga.','Wafanyakazi huona kesi wanazoruhusiwa tu, kila mawasiliano yanapofunuliwa hurekodiwa, na rufaa inahitaji watu wawili tofauti kukubali.','Mameneja huona hesabu tu, na namba ndogo hufichwa ili hakuna anayetambuliwa.','Kijana akiwa hatarini mara moja, tunaweza kuhusisha mamlaka na kushiriki tu kinachohitajika.'],
  'law'=>'Haki na sheria', 'law.t'=>'Bomaveda imeundwa kuzingatia Katiba ya Kenya (2010) na Sheria ya Ulinzi wa Data (2019).',
  'art'=>[['Ibara ya 27: Usawa','Upatikanaji sawa wa ulinzi wa jamii, bila kujali hadhi au simu yako.'],['Ibara ya 28: Utu','Hulinda vijana na wanaoripoti dhidi ya kufedheheshwa hadharani.'],['Ibara ya 29: Uhuru na usalama wa mtu','Ulinzi dhidi ya vurugu, vitisho na kuandikishwa.'],['Ibara ya 43: Haki za kiuchumi na kijamii','Haki ya huduma za jamii, afya na ustawi wa msingi.']],
  'dp'=>'Ulinzi wa data kwa vitendo',
  'd'=>['Imesimbwa: maelezo ya ripoti na mawasiliano yoyote husimbwa kabla ya kuhifadhiwa.','Hakuna namba ya simu wala IP inayohifadhiwa: USSD haisomi namba ya mpigaji, na programu ya wavuti haihifadhi anwani yako.','Ridhaa tangu mwanzo: chaguo wazi kwa lugha rahisi kabla ya kutuma chochote. Mawasiliano ni ya hiari kila wakati.','Usiri umejengwa ndani ya mfumo, si ahadi ya maneno tu.'],
  'cta'=>'Uko tayari kuongea na mtu?', 'cta.t'=>'Huhitaji kutoa jina lako.'
]];
page($A[lang() ?? 'en']['title'], function () use ($A) {
  $a = $A[lang() ?? 'en']; $l = lang() ?? 'en'; $li = fn(array $xs) => implode('', array_map(fn($x) => '<li>'.e($x).'</li>', $xs));
  echo '<section class="band"><div class="wrap"><p class="kick">'.e($a['kick']).'</p><h1>'.e($a['h1']).'</h1><p class="lead">'.e($a['lead']).'</p></div></section>';
  echo '<section class="band white"><div class="wrap"><h2>'.e($a['why']).'</h2><p>'.e($a['why.t']).'</p><div class="grid three pillars">';
  foreach ($a['pr'] as $p) echo '<div class="card"><h3>'.e($p[0]).'</h3><p>'.e($p[1]).'</p></div>';
  echo '</div><div class="card"><h3>'.e($a['ussd']).'</h3><p class="code">'.e(ussd_code()).'</p><p>'.e($a['ussd.t']).'</p></div></div></section>';
  echo '<section class="band"><div class="wrap"><h2>'.e($a['mv']).'</h2><div class="grid"><div class="card"><h3>'.e($a['mission']).'</h3><p>'.e($a['mission.t']).'</p></div><div class="card"><h3>'.e($a['vision']).'</h3><p class="lead"><strong>'.e($a['vision.q']).'</strong></p><p>'.e($a['vision.t']).'</p></div></div></div></section>';
  echo '<section class="band white"><div class="wrap"><h2>'.e($a['fv']).'</h2><div class="split"><div><h3>'.e($a['focus']).'</h3><ul class="ticks">'.$li($a['f']).'</ul></div><div><h3>'.e($a['values']).'</h3><ul class="ticks">'.$li($a['v']).'</ul></div></div></div></section>';
  echo '<section class="band"><div class="wrap"><h2>'.e($a['who']).'</h2><div class="split"><div class="card"><h3>'.e($a['who1']).'</h3><ul>'.$li($a['w1']).'</ul></div><div class="card"><h3>'.e($a['who2']).'</h3><ul>'.$li($a['w2']).'</ul></div></div></div></section>';
  echo '<section class="band white"><div class="wrap"><h2>'.e($a['net']).'</h2><p>'.e($a['net.t']).'</p><div class="scroll" role="region" tabindex="0" aria-label="'.e($a['net']).'"><table class="tbl"><thead><tr>';
  foreach ($a['th'] as $h) echo '<th scope="col">'.e($h).'</th>';
  echo '</tr></thead><tbody>'; foreach ($a['rows'] as $r) echo '<tr><th scope="row">'.e($r[0]).'</th><td>'.e($r[1]).'</td><td>'.e($r[2]).'</td></tr>'; echo '</tbody></table></div></div></section>';
  echo '<section class="band"><div class="wrap split"><div><h2>'.e($a['how']).'</h2><ul class="ticks">'.$li($a['h']).'</ul></div><div class="card"><h2>'.e($a['dp']).'</h2><ul class="ticks">'.$li($a['d']).'</ul></div></div></section>';
  echo '<section class="band white"><div class="wrap"><h2>'.e($a['law']).'</h2><p>'.e($a['law.t']).'</p><div class="grid four pillars">';
  foreach ($a['art'] as $r) echo '<div class="card"><h3>'.e($r[0]).'</h3><p>'.e($r[1]).'</p></div>';
  echo '</div></div></section>';
  echo '<section class="band"><div class="wrap chooser"><h2>'.e($a['cta']).'</h2><p>'.e($a['cta.t']).'</p><p class="actions"><a class="btn" href="'.e(u('/submit-concern')).'">'.e(t('share')).'</a> <a class="btn alt" href="'.e(u('/request-support')).'">'.e(t('support')).'</a></p></div></section>';
}, false, false, 'home');
