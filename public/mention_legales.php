<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';

$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));

$sections = $lang === 'en' ? [
    ['Site publisher', 'This website is published by <strong>CYNA-IT</strong>, a company registered in France.<br>Registered office: 10 Rue de Penthièvre, 75008 Paris, France.<br>SIRET: 91371103200015.<br>Email: contact@cyna-it.fr.'],
    ['Publication director', 'The publication director is the legal representative of CYNA-IT.'],
    ['Hosting', 'The application is hosted on cloud infrastructure (Render / Supabase) located in the European Union.'],
    ['Intellectual property', 'All content on this site (texts, logos, graphics) is the exclusive property of CYNA-IT and may not be reproduced without prior authorization.'],
    ['Personal data', 'In accordance with the GDPR, you have the right to access, rectify and delete your personal data. To exercise these rights, contact us at contact@cyna-it.fr.'],
    ['Cookies', 'This site uses session cookies strictly necessary for its operation (authentication, cart).'],
] : ($lang === 'ar' ? [
    ['ناشر الموقع', 'هذا الموقع منشور من قبل <strong>CYNA-IT</strong>، شركة مسجلة في فرنسا.<br>المقر: 10 Rue de Penthièvre، 75008 باريس، فرنسا.<br>SIRET: 91371103200015.<br>البريد الإلكتروني: contact@cyna-it.fr.'],
    ['مدير النشر', 'مدير النشر هو الممثل القانوني لشركة CYNA-IT.'],
    ['الاستضافة', 'يُستضاف التطبيق على بنية تحتية سحابية (Render / Supabase) داخل الاتحاد الأوروبي.'],
    ['الملكية الفكرية', 'جميع المحتويات على هذا الموقع ملك حصري لشركة CYNA-IT ولا يجوز نسخها دون إذن مسبق.'],
    ['البيانات الشخصية', 'وفقاً للائحة GDPR، لديك الحق في الوصول إلى بياناتك الشخصية وتصحيحها وحذفها عبر contact@cyna-it.fr.'],
    ['ملفات تعريف الارتباط', 'يستخدم هذا الموقع ملفات تعريف ارتباط ضرورية لتشغيله (المصادقة، السلة).'],
] : ($lang === 'he' ? [
    ['מפרסם האתר', 'אתר זה מתפרסם על ידי <strong>CYNA-IT</strong>, חברה הרשומה בצרפת.<br>משרד רשום: 10 Rue de Penthièvre, 75008 פריז, צרפת.<br>SIRET: 91371103200015.<br>אימייל: contact@cyna-it.fr.'],
    ['מנהל הפרסום', 'מנהל הפרסום הוא הנציג החוקי של CYNA-IT.'],
    ['אחסון', 'היישום מאוחסן בתשתית ענן (Render / Supabase) הממוקמת באיחוד האירופי.'],
    ['קניין רוחני', 'כל התוכן באתר זה הוא רכושה הבלעדי של CYNA-IT ואין לשכפלו ללא אישור מראש.'],
    ['מידע אישי', 'בהתאם ל-GDPR, יש לך זכות לגשת, לתקן ולמחוק את המידע האישי שלך דרך contact@cyna-it.fr.'],
    ['עוגיות', 'אתר זה משתמש בעוגיות הכרחיות לתפעולו (אימות, עגלה).'],
] : [
    ['Éditeur du site', "Ce site est édité par <strong>CYNA-IT</strong>, société immatriculée en France.<br>Siège social : 10 Rue de Penthièvre, 75008 Paris, France.<br>SIRET : 91371103200015.<br>Email : contact@cyna-it.fr."],
    ['Directeur de la publication', "Le directeur de la publication est le représentant légal de CYNA-IT."],
    ['Hébergement', "L'application est hébergée sur une infrastructure cloud (Render / Supabase) située au sein de l'Union européenne."],
    ['Propriété intellectuelle', "L'ensemble des contenus de ce site (textes, logos, graphismes) est la propriété exclusive de CYNA-IT et ne peut être reproduit sans autorisation préalable."],
    ['Données personnelles', "Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et de suppression de vos données personnelles. Pour exercer ces droits, contactez-nous à contact@cyna-it.fr."],
    ['Journal d\'activité', "Pour la sécurité, la prévention de la fraude et le suivi des commandes, nous enregistrons certaines actions lorsque vous êtes connecté : création de commande, pages du parcours d'achat (produit, panier, paiement), modifications de compte. Nous ne journalisons pas la navigation générale (catalogue, recherche). Les adresses IP sont tronquées. Les journaux sont conservés 12 mois maximum puis supprimés. Lors de la suppression de votre compte, les traces de navigation sont effacées ; les événements liés aux commandes peuvent être conservés de façon anonymisée pour nos obligations légales."],
    ['Cookies', "Ce site utilise des cookies de session strictement nécessaires à son fonctionnement (authentification, panier)."],
]));

$page_title = t('legal');
require __DIR__ . '/_static_layout.php';
