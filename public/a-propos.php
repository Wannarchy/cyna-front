<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';

$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));

$sections = $lang === 'en' ? [
    ['Who we are', '<strong>CYNA</strong> is a cybersecurity company specializing in SaaS solutions for businesses. Our mission: make advanced security accessible, simple and proactive.'],
    ['Our services', 'We design and operate managed detection and response services: <strong>SOC</strong> (24/7 monitoring), <strong>EDR</strong> (endpoint protection) and <strong>XDR</strong> (multi-source correlation).'],
    ['Our commitment', '24/7 support included in all subscriptions, rapid SaaS deployment, real-time alerting and strengthened compliance.'],
    ['Contact', 'CYNA-IT — 10 Rue de Penthièvre, 75008 Paris, France.<br>Email: contact@cyna-it.fr — Hours: Mon–Fri, 9am–6pm.'],
] : ($lang === 'ar' ? [
    ['من نحن', '<strong>CYNA</strong> شركة متخصصة في الأمن السيبراني وحلول SaaS للشركات. مهمتنا: جعل الأمان المتقدم متاحاً وبسيطاً واستباقياً.'],
    ['خدماتنا', 'نصمم ونشغّل خدمات الكشف والاستجابة المُدارة: <strong>SOC</strong> (مراقبة 24/7)، <strong>EDR</strong> (حماية النقاط الطرفية) و<strong>XDR</strong> (الربط متعدد المصادر).'],
    ['التزامنا', 'دعم 24/7 مشمول في جميع الاشتراكات، نشر سريع عبر SaaS، تنبيهات في الوقت الفعلي وامتثال مُعزَّز.'],
    ['اتصل بنا', 'CYNA-IT — 10 Rue de Penthièvre، 75008 باريس، فرنسا.<br>البريد الإلكتروني: contact@cyna-it.fr — ساعات العمل: الاثنين–الجمعة، 9ص–6م.'],
] : ($lang === 'he' ? [
    ['מי אנחנו', '<strong>CYNA</strong> היא חברת אבטחת סייבר המתמחה בפתרונות SaaS לעסקים. המשימה שלנו: להפוך אבטחה מתקדמת לנגישה, פשוטה ויזומה.'],
    ['השירותים שלנו', 'אנו מתכננים ומפעילים שירותי זיהוי ותגובה מנוהלים: <strong>SOC</strong> (ניטור 24/7), <strong>EDR</strong> (הגנת קצה) ו-<strong>XDR</strong> (מתאם רב-מקורות).'],
    ['המחויבות שלנו', 'תמיכה 24/7 כלולה בכל המנויים, פריסת SaaS מהירה, התראות בזמן אמת וציות מחוזק.'],
    ['צור קשר', 'CYNA-IT — 10 Rue de Penthièvre, 75008 פריז, צרפת.<br>אימייל: contact@cyna-it.fr — שעות: שני–שישי, 9:00–18:00.'],
] : [
    ['Qui sommes-nous', "<strong>CYNA</strong> est une entreprise de cybersécurité spécialisée dans les solutions SaaS pour les entreprises. Notre mission : rendre la sécurité avancée accessible, simple et proactive."],
    ['Nos services', "Nous concevons et opérons des services managés de détection et de réponse : <strong>SOC</strong> (supervision 24/7), <strong>EDR</strong> (protection des endpoints) et <strong>XDR</strong> (corrélation multi-sources)."],
    ['Notre engagement', "Support 24/7 inclus dans tous les abonnements, déploiement SaaS rapide, alertes en temps réel et conformité renforcée."],
    ['Contact', "CYNA-IT — 10 Rue de Penthièvre, 75008 Paris, France.<br>Email : contact@cyna-it.fr — Horaires : Lun–Ven, 9h–18h."],
]));

$page_title = t('about');
require __DIR__ . '/_static_layout.php';
