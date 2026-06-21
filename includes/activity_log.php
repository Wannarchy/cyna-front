<?php

/**
 * Journal d'activité côté front — aligné sur config/audit.php (API).
 * Minimisation RGPD : uniquement le parcours achat / compte, utilisateur authentifié.
 */
function cyna_audit_allowed_pages(): array
{
    return [
        'produit.php',
        'panier.php',
        'panier_add.php',
        'checkout.php',
        'checkout_submit.php',
        'confirmation.php',
        'mon-compte.php',
        'mes-commandes.php',
        'mes-abonnements.php',
        'adresses.php',
        'paiements.php',
        'paiement_refuse.php',
    ];
}

function cyna_audit_maybe_log_page(string $script): void
{
    $script = basename(trim($script));

    if ($script === '' || ! in_array($script, cyna_audit_allowed_pages(), true)) {
        return;
    }

    if (empty($_SESSION['api_token'])) {
        return;
    }

    try {
        api_client()->logPageView($script);
    } catch (Throwable) {
    }
}
