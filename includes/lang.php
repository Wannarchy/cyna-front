<?php
/**
 * CYNA — Système multi-langue avec support RTL
 * Langues : fr, en, ar (arabe), he (hébreu)
 * Usage : require_once 'lang.php'; echo t('welcome');
 * Changer la langue : ?lang=ar ou ?lang=he
 */

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en', 'ar', 'he'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'fr';

// Langues RTL
$rtl_langs = ['ar', 'he'];
$is_rtl    = in_array($lang, $rtl_langs);
$dir       = $is_rtl ? 'rtl' : 'ltr';

$translations = [

    'fr' => [
        'nav_catalogue'    => 'Catalogue',
        'nav_login'        => 'Connexion',
        'nav_register'     => "S'inscrire",
        'nav_account'      => 'Mon compte',
        'nav_logout'       => 'Déconnexion',
        'nav_cart'         => 'Panier',
        'hero_title'       => 'Sécurisez votre entreprise avec CYNA',
        'hero_sub'         => 'Solutions SaaS de cybersécurité — SOC, EDR, XDR',
        'hero_cta'         => 'Découvrir nos services',
        'hero_cta2'        => 'Nous contacter',
        'featured_title'   => 'Nos solutions phares',
        'per_month'        => '/ mois',
        'per_year'         => '/ an',
        'available'        => 'Disponible',
        'unavailable'      => 'Indisponible',
        'see_offer'        => "Voir l'offre",
        'add_cart'         => 'Ajouter au panier',
        'catalogue_title'  => 'Catalogue de solutions SaaS',
        'all_categories'   => 'Toutes les catégories',
        'services_count'   => 'service(s)',
        'no_product'       => 'Aucun produit dans cette catégorie.',
        'cart_title'       => 'Mon panier',
        'cart_empty'       => 'Votre panier est vide.',
        'cart_total'       => 'Total',
        'checkout'         => 'Commander',
        'monthly'          => 'Mensuel',
        'yearly'           => 'Annuel',
        'remove'           => 'Supprimer',
        'checkout_title'   => 'Finaliser la commande',
        'billing_info'     => 'Informations de facturation',
        'payment_info'     => 'Informations de paiement',
        'confirm_pay'      => 'Confirmer et payer',
        'promo_code'       => 'Code promo',
        'apply'            => 'Appliquer',
        'order_confirmed'  => 'Commande confirmée !',
        'order_thanks'     => 'Merci pour votre confiance.',
        'view_orders'      => 'Voir mes commandes',
        'back_home'        => "Retour à l'accueil",
        'my_account'       => 'Mon compte',
        'my_orders'        => 'Mes commandes',
        'my_subscriptions' => 'Mes abonnements',
        'my_addresses'     => 'Mes adresses',
        'my_payments'      => 'Mes paiements',
        'profile'          => 'Mon profil',
        'security'         => 'Sécurité',
        'save'             => 'Enregistrer',
        'cancel'           => 'Annuler',
        'delete'           => 'Supprimer',
        'edit'             => 'Modifier',
        'contact'          => 'Contact',
        'about'            => 'À propos',
        'legal'            => 'Mentions légales',
        'cgu'              => 'CGU',
        'copyright'        => '© 2025 CYNA-IT',
    ],

    'en' => [
        'nav_catalogue'    => 'Catalogue',
        'nav_login'        => 'Login',
        'nav_register'     => 'Sign up',
        'nav_account'      => 'My account',
        'nav_logout'       => 'Logout',
        'nav_cart'         => 'Cart',
        'hero_title'       => 'Secure your business with CYNA',
        'hero_sub'         => 'SaaS cybersecurity solutions — SOC, EDR, XDR',
        'hero_cta'         => 'Discover our services',
        'hero_cta2'        => 'Contact us',
        'featured_title'   => 'Our featured solutions',
        'per_month'        => '/ month',
        'per_year'         => '/ year',
        'available'        => 'Available',
        'unavailable'      => 'Unavailable',
        'see_offer'        => 'View offer',
        'add_cart'         => 'Add to cart',
        'catalogue_title'  => 'SaaS solutions catalogue',
        'all_categories'   => 'All categories',
        'services_count'   => 'service(s)',
        'no_product'       => 'No products in this category.',
        'cart_title'       => 'My cart',
        'cart_empty'       => 'Your cart is empty.',
        'cart_total'       => 'Total',
        'checkout'         => 'Checkout',
        'monthly'          => 'Monthly',
        'yearly'           => 'Yearly',
        'remove'           => 'Remove',
        'checkout_title'   => 'Complete your order',
        'billing_info'     => 'Billing information',
        'payment_info'     => 'Payment information',
        'confirm_pay'      => 'Confirm and pay',
        'promo_code'       => 'Promo code',
        'apply'            => 'Apply',
        'order_confirmed'  => 'Order confirmed!',
        'order_thanks'     => 'Thank you for your trust.',
        'view_orders'      => 'View my orders',
        'back_home'        => 'Back to home',
        'my_account'       => 'My account',
        'my_orders'        => 'My orders',
        'my_subscriptions' => 'My subscriptions',
        'my_addresses'     => 'My addresses',
        'my_payments'      => 'My payments',
        'profile'          => 'My profile',
        'security'         => 'Security',
        'save'             => 'Save',
        'cancel'           => 'Cancel',
        'delete'           => 'Delete',
        'edit'             => 'Edit',
        'contact'          => 'Contact',
        'about'            => 'About',
        'legal'            => 'Legal notice',
        'cgu'              => 'Terms of service',
        'copyright'        => '© 2025 CYNA-IT',
    ],

    'ar' => [
        'nav_catalogue'    => 'الكتالوج',
        'nav_login'        => 'تسجيل الدخول',
        'nav_register'     => 'إنشاء حساب',
        'nav_account'      => 'حسابي',
        'nav_logout'       => 'تسجيل الخروج',
        'nav_cart'         => 'السلة',
        'hero_title'       => 'احمِ مؤسستك مع CYNA',
        'hero_sub'         => 'حلول الأمن السيبراني SaaS — SOC و EDR و XDR',
        'hero_cta'         => 'اكتشف خدماتنا',
        'hero_cta2'        => 'تواصل معنا',
        'featured_title'   => 'حلولنا المميزة',
        'per_month'        => '/ شهر',
        'per_year'         => '/ سنة',
        'available'        => 'متاح',
        'unavailable'      => 'غير متاح',
        'see_offer'        => 'عرض التفاصيل',
        'add_cart'         => 'أضف إلى السلة',
        'catalogue_title'  => 'كتالوج حلول SaaS',
        'all_categories'   => 'جميع الفئات',
        'services_count'   => 'خدمة',
        'no_product'       => 'لا توجد منتجات في هذه الفئة.',
        'cart_title'       => 'سلة التسوق',
        'cart_empty'       => 'سلتك فارغة.',
        'cart_total'       => 'المجموع',
        'checkout'         => 'إتمام الطلب',
        'monthly'          => 'شهري',
        'yearly'           => 'سنوي',
        'remove'           => 'حذف',
        'checkout_title'   => 'إتمام الطلب',
        'billing_info'     => 'معلومات الفوترة',
        'payment_info'     => 'معلومات الدفع',
        'confirm_pay'      => 'تأكيد الدفع',
        'promo_code'       => 'رمز الخصم',
        'apply'            => 'تطبيق',
        'order_confirmed'  => 'تم تأكيد الطلب!',
        'order_thanks'     => 'شكراً لثقتك بنا.',
        'view_orders'      => 'عرض طلباتي',
        'back_home'        => 'العودة للرئيسية',
        'my_account'       => 'حسابي',
        'my_orders'        => 'طلباتي',
        'my_subscriptions' => 'اشتراكاتي',
        'my_addresses'     => 'عناويني',
        'my_payments'      => 'طرق الدفع',
        'profile'          => 'ملفي الشخصي',
        'security'         => 'الأمان',
        'save'             => 'حفظ',
        'cancel'           => 'إلغاء',
        'delete'           => 'حذف',
        'edit'             => 'تعديل',
        'contact'          => 'اتصل بنا',
        'about'            => 'من نحن',
        'legal'            => 'الإشعارات القانونية',
        'cgu'              => 'شروط الاستخدام',
        'copyright'        => '© 2025 CYNA-IT',
    ],

    'he' => [
        'nav_catalogue'    => 'קטלוג',
        'nav_login'        => 'התחברות',
        'nav_register'     => 'הרשמה',
        'nav_account'      => 'החשבון שלי',
        'nav_logout'       => 'התנתקות',
        'nav_cart'         => 'עגלה',
        'hero_title'       => 'אבטח את העסק שלך עם CYNA',
        'hero_sub'         => 'פתרונות אבטחת סייבר SaaS — SOC, EDR, XDR',
        'hero_cta'         => 'גלה את השירותים שלנו',
        'hero_cta2'        => 'צור קשר',
        'featured_title'   => 'הפתרונות המובילים שלנו',
        'per_month'        => '/ חודש',
        'per_year'         => '/ שנה',
        'available'        => 'זמין',
        'unavailable'      => 'לא זמין',
        'see_offer'        => 'צפה בהצעה',
        'add_cart'         => 'הוסף לעגלה',
        'catalogue_title'  => 'קטלוג פתרונות SaaS',
        'all_categories'   => 'כל הקטגוריות',
        'services_count'   => 'שירות(ים)',
        'no_product'       => 'אין מוצרים בקטגוריה זו.',
        'cart_title'       => 'עגלת הקניות',
        'cart_empty'       => 'העגלה שלך ריקה.',
        'cart_total'       => 'סך הכל',
        'checkout'         => 'לתשלום',
        'monthly'          => 'חודשי',
        'yearly'           => 'שנתי',
        'remove'           => 'הסר',
        'checkout_title'   => 'השלמת ההזמנה',
        'billing_info'     => 'פרטי חיוב',
        'payment_info'     => 'פרטי תשלום',
        'confirm_pay'      => 'אשר ושלם',
        'promo_code'       => 'קוד קידום מכירות',
        'apply'            => 'החל',
        'order_confirmed'  => 'ההזמנה אושרה!',
        'order_thanks'     => 'תודה על אמונך בנו.',
        'view_orders'      => 'צפה בהזמנות שלי',
        'back_home'        => 'חזרה לדף הבית',
        'my_account'       => 'החשבון שלי',
        'my_orders'        => 'ההזמנות שלי',
        'my_subscriptions' => 'המנויים שלי',
        'my_addresses'     => 'הכתובות שלי',
        'my_payments'      => 'אמצעי תשלום',
        'profile'          => 'הפרופיל שלי',
        'security'         => 'אבטחה',
        'save'             => 'שמור',
        'cancel'           => 'בטל',
        'delete'           => 'מחק',
        'edit'             => 'ערוך',
        'contact'          => 'צור קשר',
        'about'            => 'אודות',
        'legal'            => 'הצהרה משפטית',
        'cgu'              => 'תנאי שימוש',
        'copyright'        => '© 2025 CYNA-IT',
    ],

];

if (!function_exists('t')) {
    function t($key) {
        global $translations, $lang;
        return $translations[$lang][$key] ?? $translations['fr'][$key] ?? $key;
    }
}

if (!function_exists('lang_switcher')) {
    function lang_switcher() {
        global $lang;

        static $stylesPrinted = false;

        $langs = [
            'fr' => ['flag' => '🇫🇷', 'label' => 'FR', 'name' => 'Français'],
            'en' => ['flag' => '🇬🇧', 'label' => 'EN', 'name' => 'English'],
            'ar' => ['flag' => '🇸🇦', 'label' => 'AR', 'name' => 'العربية'],
            'he' => ['flag' => '🇮🇱', 'label' => 'HE', 'name' => 'עברית'],
        ];

        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $query = $_GET;
        $current = $langs[$lang] ?? $langs['fr'];

        $html = '';

        if (! $stylesPrinted) {
            $html .= <<<'CSS'
<style>
.cyna-lang-dropdown{position:relative;display:inline-block}
.cyna-lang-dropdown>summary{list-style:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;padding:5px 10px;border-radius:20px;color:#fff;border:1px solid rgba(38,208,206,.4);background:rgba(38,208,206,.12);transition:all .15s;user-select:none}
.cyna-lang-dropdown>summary::-webkit-details-marker{display:none}
.cyna-lang-dropdown>summary::after{content:"▾";font-size:.6rem;opacity:.75;margin-left:2px}
.cyna-lang-dropdown[open]>summary{border-color:rgba(38,208,206,.55);background:rgba(38,208,206,.18)}
.cyna-lang-menu{position:absolute;top:calc(100% + 6px);right:0;min-width:148px;background:#131b2e;border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:6px;box-shadow:0 12px 32px rgba(0,0,0,.35);z-index:1200;display:flex;flex-direction:column;gap:2px}
html[dir="rtl"] .cyna-lang-menu{right:auto;left:0}
.cyna-lang-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;font-size:.78rem;font-weight:500;color:rgba(255,255,255,.75);transition:background .15s,color .15s}
.cyna-lang-item:hover{background:rgba(255,255,255,.06);color:#fff}
.cyna-lang-item.is-active{background:rgba(38,208,206,.12);color:#fff;font-weight:700;pointer-events:none}
.cyna-lang-item .code{margin-left:auto;font-size:.65rem;color:rgba(255,255,255,.35)}
html[dir="rtl"] .cyna-lang-item .code{margin-left:0;margin-right:auto}
</style>
CSS;
            $stylesPrinted = true;
        }

        $html .= '<details class="cyna-lang-dropdown">';
        $html .= '<summary>'.$current['flag'].' '.$current['label'].'</summary>';
        $html .= '<div class="cyna-lang-menu">';

        foreach ($langs as $code => $info) {
            $query['lang'] = $code;
            $url = $currentPath.'?'.http_build_query($query);
            $active = $code === $lang;
            $class = 'cyna-lang-item'.($active ? ' is-active' : '');
            $html .= '<a class="'.$class.'" href="'.htmlspecialchars($url).'">';
            $html .= '<span>'.$info['flag'].'</span>';
            $html .= '<span>'.$info['name'].'</span>';
            $html .= '<span class="code">'.$info['label'].'</span>';
            $html .= '</a>';
        }

        $html .= '</div></details>';

        return $html;
    }
}

if (!function_exists('rtl_attrs')) {
    function rtl_attrs() {
        global $lang, $dir;
        return 'lang="' . $lang . '" dir="' . $dir . '"';
    }
}