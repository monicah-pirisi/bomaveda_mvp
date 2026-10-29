<?php
// Editable content. Change lists here, not in code. Check the emergency numbers before launch.
return [
  'categories' => ['sudden_isolation','harmful_online_influence','school_dropout_risk','emotional_distress','violent_rhetoric','recruitment_concerns','substance_abuse','family_community_conflict'],
  'urgency' => ['low','moderate','urgent'],
  'status' => ['submitted','under_review','verified','intervention_assigned','active_support','monitoring','resolved'],
  'wards' => [
    'Kilifi North'=>['Tezo','Sokoni','Kibarani','Dabaso','Matsangoni','Watamu','Mnarani'],
    'Kilifi South'=>['Junju','Mwarakaya','Shimo La Tewa','Chasimba','Mtepeni'],
    'Kaloleni'=>['Mariakani','Kayafungo','Kaloleni','Mwanamwinga'],
    'Rabai'=>['Rabai/Kisurutini','Ruruma','Mwawesa','Kambe/Ribe'],
    'Ganze'=>['Ganze','Bamba','Jaribuni','Sokoke'],
    'Malindi'=>['Jilore','Kakuyuni','Ganda','Malindi Town','Shella'],
    'Magarini'=>['Marafa','Magarini','Gongoni','Adu','Garashi','Sabaki'],
  ],
  'support_types' => ['mentorship','counselling','education','jobs'],
  'agencies' => ['County social services','Child protection services','Partner NGO or civil society group','Health or counselling service','Authorities (rare, immediate danger only)'],
  'partners' => ['Kilifi County','Haki Africa','KECOSCE','Coast Education Centre','Sauti ya Vijana'],
  'guides' => [
    ['title'=>['en'=>'Early warning signs','sw'=>'Dalili za mapema'],
     'body'=>['en'=>'Pulling away from family or friends. Skipping school. New secret friends or online contacts. Strong anger, sadness or hopelessness. Talk of joining a risky group. Drug or alcohol use. One sign is not proof. Look for a pattern.',
              'sw'=>'Kujitenga na familia au marafiki. Kuacha shule. Marafiki wapya wa siri au watu mtandaoni. Hasira kali, huzuni au kukata tamaa. Maneno ya kujiunga na kikundi hatari. Dawa za kulevya au pombe. Dalili moja si uthibitisho. Tafuta mfululizo.']],
    ['title'=>['en'=>'How to talk with a young person','sw'=>'Jinsi ya kuzungumza na kijana'],
     'body'=>['en'=>'Choose a quiet, private time. Listen more than you speak. Do not shout or blame. Ask what they need. Tell them you care and will help find support.',
              'sw'=>'Chagua wakati wa utulivu na faragha. Sikiliza zaidi kuliko kuongea. Usipige kelele wala kulaumu. Uliza anahitaji nini. Mwambie unamjali na utasaidia kutafuta msaada.']],
    ['title'=>['en'=>'Keep your case number safe','sw'=>'Linda namba yako ya kesi'],
     'body'=>['en'=>'Write it on paper. Do not save it on a shared phone. Do not take a screenshot on a shared phone. Share it only with someone you trust.',
              'sw'=>'Iandike kwenye karatasi. Usiihifadhi kwenye simu ya pamoja. Usipige picha ya skrini kwenye simu ya pamoja. Mshirikishe tu unayemwamini.']],
    ['title'=>['en'=>'In an emergency','sw'=>'Wakati wa dharura'],
     'body'=>['en'=>'If a life is in danger, call the emergency numbers on this page first. Then you can also share a concern with us.',
              'sw'=>'Maisha yakiwa hatarini, piga namba za dharura zilizo kwenye ukurasa huu kwanza. Kisha unaweza pia kutuma wasiwasi kwetu.']],
  ],
  'emergency' => [
    ['num'=>'999 / 112','en'=>'Emergency','sw'=>'Dharura'],
    ['num'=>'116','en'=>'Child helpline','sw'=>'Msaada wa watoto'],
    ['num'=>'1195','en'=>'Violence help','sw'=>'Msaada wa ukatili'],
  ],
];
