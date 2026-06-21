<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/lang.php';

$code = strtoupper(trim($_POST['code'] ?? ''));
$total = (float) ($_POST['total'] ?? 0);

$msg_required = $lang === 'en' ? 'Code required.' : ($lang === 'ar' ? 'الرمز مطلوب.' : ($lang === 'he' ? 'קוד נדרש.' : 'Code requis.'));
$msg_invalid = $lang === 'en' ? 'Invalid, expired or exhausted code.' : ($lang === 'ar' ? 'رمز غير صالح أو منتهي الصلاحية.' : ($lang === 'he' ? 'קוד לא תקין, פג תוקפו או נוצל.' : 'Code invalide, expiré ou épuisé.'));
$msg_auth = $lang === 'en' ? 'Please sign in to apply a promo code.' : ($lang === 'ar' ? 'يرجى تسجيل الدخول لتطبيق الرمز.' : ($lang === 'he' ? 'התחבר כדי להחיל קוד.' : 'Connectez-vous pour appliquer un code promo.'));
$msg_applied = $lang === 'en' ? 'Code applied! Discount: ' : ($lang === 'ar' ? 'تم تطبيق الرمز! الخصم: ' : ($lang === 'he' ? 'קוד הוחל! הנחה: ' : 'Code appliqué ! Réduction : '));

if (empty($code)) {
    echo json_encode(['valid' => false, 'message' => $msg_required]);
    exit;
}

if (empty($_SESSION['api_token'])) {
    echo json_encode(['valid' => false, 'message' => $msg_auth]);
    exit;
}

try {
    $data = api_client()->validatePromoCode($code, $total);
    $discount = (float) ($data['discount'] ?? 0);
    $new_total = (float) ($data['final_amount'] ?? max(0, $total - $discount));

    echo json_encode([
        'valid' => true,
        'discount' => $discount,
        'new_total' => $new_total,
        'message' => $msg_applied.number_format($discount, 2, ',', ' ').' €',
    ]);
} catch (RuntimeException $e) {
    echo json_encode(['valid' => false, 'message' => $e->getMessage()]);
} catch (Throwable) {
    echo json_encode(['valid' => false, 'message' => $msg_invalid]);
}
