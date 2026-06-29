<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';

$est_connecte = isset($_SESSION['utilisateur_id']);

$sections = $lang === 'en' ? [
    ['1. Purpose', 'These terms of service govern the use of the CYNA platform and the SaaS cybersecurity services (SOC, EDR, XDR) offered by CYNA-IT.'],
    ['2. Account', 'Access to subscriptions requires the creation of an account with a valid, confirmed email address. You are responsible for keeping your credentials confidential.'],
    ['3. Subscriptions', 'Services are offered on a monthly or annual basis. The annual subscription includes a 10% discount. Renewal is automatic until cancellation.'],
    ['4. Payment', 'Payments are processed securely via Stripe. We never store your full card number on our servers.'],
    ['5. Cancellation', 'You may cancel your subscription at any time from your account. Cancellation takes effect at the end of the current billing period, with no additional charge.'],
    ['6. Liability', 'CYNA-IT undertakes to provide the services with care but cannot be held liable for indirect damages resulting from the use of the services.'],
    ['7. Applicable law', 'These terms are governed by French law. Any dispute falls under the jurisdiction of the competent French courts.'],
] : ($lang === 'ar' ? [
    ['1. الغرض', 'تحكم شروط الاستخدام هذه استخدام منصة CYNA وخدمات الأمن السيبراني SaaS (SOC، EDR، XDR) المقدمة من CYNA-IT.'],
    ['2. الحساب', 'يتطلب الوصول إلى الاشتراكات إنشاء حساب ببريد إلكتروني صالح ومؤكد. أنت مسؤول عن سرية بيانات الدخول الخاصة بك.'],
    ['3. الاشتراكات', 'تُقدَّم الخدمات شهرياً أو سنوياً. يتضمن الاشتراك السنوي خصماً بنسبة 10%. التجديد تلقائي حتى الإلغاء.'],
    ['4. الدفع', 'تُعالَج المدفوعات بأمان عبر Stripe. لا نخزّن أبداً رقم بطاقتك الكامل على خوادمنا.'],
    ['5. الإلغاء', 'يمكنك إلغاء اشتراكك في أي وقت من حسابك. يسري الإلغاء في نهاية فترة الفوترة الحالية دون رسوم إضافية.'],
    ['6. المسؤولية', 'تلتزم CYNA-IT بتقديم الخدمات بعناية لكنها لا تتحمل مسؤولية الأضرار غير المباشرة الناتجة عن استخدام الخدمات.'],
    ['7. القانون المطبق', 'تخضع هذه الشروط للقانون الفرنسي. أي نزاع يقع ضمن اختصاص المحاكم الفرنسية المختصة.'],
] : ($lang === 'he' ? [
    ['1. מטרה', 'תנאי שימוש אלה מסדירים את השימוש בפלטפורמת CYNA ובשירותי אבטחת הסייבר SaaS (SOC, EDR, XDR) של CYNA-IT.'],
    ['2. חשבון', 'גישה למנויים מחייבת יצירת חשבון עם כתובת אימייל תקפה ומאומתת. אתה אחראי לשמירת סודיות פרטי הכניסה שלך.'],
    ['3. מנויים', 'השירותים מוצעים על בסיס חודשי או שנתי. המנוי השנתי כולל הנחה של 10%. החידוש אוטומטי עד לביטול.'],
    ['4. תשלום', 'התשלומים מעובדים בצורה מאובטחת דרך Stripe. לעולם איננו שומרים את מספר הכרטיס המלא שלך בשרתינו.'],
    ['5. ביטול', 'תוכל לבטל את המנוי שלך בכל עת מחשבונך. הביטול נכנס לתוקף בסוף תקופת החיוב הנוכחית, ללא חיוב נוסף.'],
    ['6. אחריות', 'CYNA-IT מתחייבת לספק את השירותים בקפידה אך אינה אחראית לנזקים עקיפים הנובעים מהשימוש בשירותים.'],
    ['7. דין חל', 'תנאים אלה כפופים לדין הצרפתי. כל מחלוקת נתונה לסמכות בתי המשפט הצרפתיים המוסמכים.'],
] : [
    ['1. Objet', "Les présentes conditions générales d'utilisation régissent l'utilisation de la plateforme CYNA et des services SaaS de cybersécurité (SOC, EDR, XDR) proposés par CYNA-IT."],
    ['2. Compte', "L'accès aux abonnements nécessite la création d'un compte avec une adresse email valide et confirmée. Vous êtes responsable de la confidentialité de vos identifiants."],
    ['3. Abonnements', "Les services sont proposés en formule mensuelle ou annuelle. L'abonnement annuel inclut une remise de 10%. Le renouvellement est automatique jusqu'à résiliation."],
    ['4. Paiement', "Les paiements sont traités de manière sécurisée via Stripe. Nous ne conservons jamais le numéro complet de votre carte sur nos serveurs."],
    ['5. Résiliation', "Vous pouvez résilier votre abonnement à tout moment depuis votre espace compte. La résiliation prend effet à la fin de la période de facturation en cours, sans frais supplémentaires."],
    ['6. Responsabilité', "CYNA-IT s'engage à fournir les services avec soin mais ne saurait être tenue responsable des dommages indirects résultant de l'utilisation des services."],
    ['7. Droit applicable', "Les présentes conditions sont régies par le droit français. Tout litige relève de la compétence des tribunaux français compétents."],
]));

$page_title = t('cgu');
require __DIR__ . '/_static_layout.php';
