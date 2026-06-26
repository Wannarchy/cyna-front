<?php

require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/cloudinary.php';

function api_client(): ApiClient
{
    static $client = null;

    if ($client === null) {
        $client = new ApiClient();
    }

    return $client;
}

/**
 * Normalise les réponses paginées Laravel (meta à la racine ou dans meta / data).
 *
 * @return array{items: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}
 */
function api_parse_paginated_response(array $body, int $defaultPerPage = 12, int $requestedPage = 1): array
{
    $payload = $body;
    $items = [];

    if (isset($body['data']) && is_array($body['data']) && array_key_exists('current_page', $body['data'])) {
        $payload = $body['data'];
        $items = is_array($payload['data'] ?? null) ? $payload['data'] : [];
    } elseif (isset($body['data']) && is_array($body['data'])) {
        $items = $body['data'];
    }

    $metaSource = is_array($body['meta'] ?? null) ? $body['meta'] : $payload;
    $perPage = max(1, (int) ($metaSource['per_page'] ?? $defaultPerPage));
    $total = (int) ($metaSource['total'] ?? count($items));
    $lastPage = (int) ($metaSource['last_page'] ?? 0);
    $currentPage = max(1, (int) ($metaSource['current_page'] ?? $requestedPage));

    if ($lastPage < 1) {
        $lastPage = max(1, (int) ceil($total / $perPage));
    }

    if (count($items) > $perPage) {
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $requestedPage), $lastPage);
        $items = array_slice($items, ($currentPage - 1) * $perPage, $perPage);
    }

    return [
        'items' => array_values($items),
        'meta' => [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ],
    ];
}

class ApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = API_BASE_URL.'/api';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function get(string $path, bool $auth = false): array
    {
        return $this->request('GET', $path, null, $auth);
    }

    public function post(string $path, array $body = [], bool $auth = false): array
    {
        return $this->request('POST', $path, $body, $auth);
    }

    public function put(string $path, array $body = [], bool $auth = false): array
    {
        return $this->request('PUT', $path, $body, $auth);
    }

    public function patch(string $path, array $body = [], bool $auth = false): array
    {
        return $this->request('PATCH', $path, $body, $auth);
    }

    public function delete(string $path, array|bool $bodyOrAuth = false, bool $auth = false): array
    {
        if (is_bool($bodyOrAuth)) {
            return $this->request('DELETE', $path, null, $bodyOrAuth);
        }

        return $this->request('DELETE', $path, $bodyOrAuth, $auth);
    }

    public function login(string $email, string $password): array
    {
        $response = $this->post('auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if (! empty($response['data']['requires_otp'])) {
            return $response;
        }

        $this->storeAuthSession($response);

        return $response;
    }

    public function verifyAdminLoginOtp(string $challengeToken, string $code): array
    {
        $response = $this->post('auth/verify-admin-otp', [
            'challenge_token' => $challengeToken,
            'code' => preg_replace('/\D+/', '', $code) ?? '',
        ]);

        $this->storeAuthSession($response);

        return $response;
    }

    public function register(string $prenom, string $nom, string $email, string $password): array
    {
        return $this->post('auth/register', [
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function logout(): void
    {
        try {
            $this->post('auth/logout', [], true);
        } catch (Throwable) {
        }
    }

    public function logPageView(string $page): void
    {
        require_once __DIR__ . '/activity_log.php';

        $page = basename(trim($page));

        if ($page === '' || ! in_array($page, cyna_audit_allowed_pages(), true)) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('cyna_session_start')) {
                cyna_session_start();
            } else {
                session_start();
            }
        }

        if (empty($_SESSION['api_token'])) {
            return;
        }

        try {
            $this->post('activity/page-view', ['page' => $page], true);
        } catch (Throwable) {
        }
    }

    public function forgotPassword(string $email): array
    {
        return $this->post('auth/forgot-password', ['email' => $email]);
    }

    public function resetPassword(string $email, string $token, string $password): array
    {
        return $this->post('auth/reset-password', [
            'email' => $email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function resendVerification(): array
    {
        return $this->post('auth/resend-verification', [], true);
    }

    public function resendVerificationByEmail(string $email = '', ?int $id = null): array
    {
        $payload = [];
        if ($id !== null && $id > 0) {
            $payload['id'] = $id;
        } else {
            $payload['email'] = $email;
        }

        return $this->post('auth/resend-verification-email', $payload);
    }

    /**
     * @param array{id?:int,email?:string,token:string} $payload
     * @return array{ok:bool,message:string,expired:bool}
     */
    public function verifyRegistrationEmail(array $payload): array
    {
        try {
            $this->post('auth/verify-email', $payload);

            return ['ok' => true, 'message' => '', 'expired' => false];
        } catch (RuntimeException $e) {
            $message = $e->getMessage();

            return [
                'ok' => false,
                'message' => $message,
                'expired' => str_contains(mb_strtolower($message), 'expir'),
            ];
        }
    }

    public function getHomepage(): array
    {
        return $this->get('homepage')['data'] ?? [];
    }

    public function getCategories(): array
    {
        return $this->get('categories')['data'] ?? [];
    }

    public function getProducts(array $query = []): array
    {
        $path = 'products';
        if ($query !== []) {
            $path .= '?'.http_build_query($query);
        }

        return $this->get($path)['data'] ?? [];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}
     */
    public function getProductsPaginated(array $query = []): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = max(1, min(48, (int) ($query['per_page'] ?? 12)));
        $path = 'products?'.http_build_query(array_merge($query, ['page' => $page, 'per_page' => $perPage]));

        return api_parse_paginated_response($this->get($path), $perPage, $page);
    }

    public function getProduct(int $id): ?array
    {
        try {
            return $this->get('products/'.$id)['data'] ?? null;
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw $e;
        }
    }

    public function getProfile(): array
    {
        return $this->get('profile', true)['data'] ?? [];
    }

    public function updateProfile(array $payload): array
    {
        return $this->put('profile', $payload, true)['data'] ?? [];
    }

    public function deleteAccount(string $currentPassword, string $confirmation = 'SUPPRIMER'): void
    {
        $this->delete('profile', [
            'current_password' => $currentPassword,
            'confirmation' => $confirmation,
        ], true);
    }

    public function getOrders(): array
    {
        return $this->get('orders', true)['data'] ?? [];
    }

    public function getOrder(int $id): ?array
    {
        try {
            return $this->get('orders/'.$id, true)['data'] ?? null;
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw $e;
        }
    }

    public function createOrder(array $payload): array
    {
        return $this->post('orders', $payload, true)['data'] ?? [];
    }

    public function getSubscriptions(): array
    {
        return $this->get('subscriptions', true)['data'] ?? [];
    }

    public function cancelSubscription(int $id): array
    {
        return $this->post('subscriptions/'.$id.'/cancel', [], true);
    }

    public function changeSubscriptionCycle(int $id, string $cycle): array
    {
        return $this->post('subscriptions/'.$id.'/change-cycle', ['cycle' => $cycle], true);
    }

    public function getAddresses(): array
    {
        return $this->get('addresses', true)['data'] ?? [];
    }

    public function createAddress(array $payload): array
    {
        return $this->post('addresses', $payload, true)['data'] ?? [];
    }

    public function updateAddress(int $id, array $payload): array
    {
        return $this->put('addresses/'.$id, $payload, true)['data'] ?? [];
    }

    public function deleteAddress(int $id): void
    {
        $this->delete('addresses/'.$id, true);
    }

    public function getPaymentMethods(): array
    {
        return $this->get('payment-methods', true)['data'] ?? [];
    }

    public function addPaymentMethod(array $payload): array
    {
        return $this->post('payment-methods', $payload, true)['data'] ?? [];
    }

    public function setDefaultPaymentMethod(string $id): void
    {
        $this->post('payment-methods/'.$id.'/default', [], true);
    }

    public function deletePaymentMethod(string $id): void
    {
        $this->delete('payment-methods/'.$id, true);
    }

    public function validatePromoCode(string $code, float $total): array
    {
        return $this->post('promo-codes/validate', [
            'code' => strtoupper(trim($code)),
            'amount' => $total,
        ], true)['data'] ?? [];
    }

    public function getBillingConfig(): array
    {
        return $this->get('billing/config')['data'] ?? [];
    }

    public function sendChatMessage(string $message, ?string $sessionId = null): array
    {
        $payload = ['user_message' => $message];
        if ($sessionId) {
            $payload['session_id'] = $sessionId;
        }

        return $this->post('chat', $payload, true)['data'] ?? [];
    }

    public function submitContact(string $email, string $sujet, string $message): array
    {
        $auth = false;
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('cyna_session_start')) {
                cyna_session_start();
            } else {
                session_start();
            }
        }
        if (! empty($_SESSION['api_token'])) {
            $auth = true;
        }

        return $this->post('contact', [
            'email' => $email,
            'sujet' => $sujet,
            'message' => $message,
        ], $auth)['data'] ?? [];
    }

    public function adminGetProducts(): array
    {
        return $this->get('admin/products', true)['data'] ?? [];
    }

    public function adminCreateProduct(array $payload): array
    {
        $response = $this->post('admin/products', $payload, true);
        $product = $response['data'] ?? [];

        if (! is_array($product) || empty($product['id'])) {
            throw new RuntimeException('Création produit : réponse API invalide.');
        }

        return [
            'data' => $product,
            'message' => (string) ($response['message'] ?? ''),
        ];
    }

    public function adminUpdateProduct(int $id, array $payload): array
    {
        $response = $this->put('admin/products/'.$id, $payload, true);

        return [
            'data' => $response['data'] ?? [],
            'message' => (string) ($response['message'] ?? ''),
        ];
    }

    public function adminDeleteProduct(int $id): void
    {
        $this->delete('admin/products/'.$id, true);
    }

    public function adminGetCategories(): array
    {
        return $this->get('admin/categories', true)['data'] ?? [];
    }

    public function adminCreateCategory(array $payload): array
    {
        return $this->post('admin/categories', $payload, true)['data'] ?? [];
    }

    public function adminUpdateCategory(int $id, array $payload): array
    {
        return $this->put('admin/categories/'.$id, $payload, true)['data'] ?? [];
    }

    public function adminDeleteCategory(int $id): void
    {
        $this->delete('admin/categories/'.$id, true);
    }

    public function adminGetOrders(): array
    {
        return $this->get('admin/orders', true)['data'] ?? [];
    }

    public function adminGetOrder(int $id): ?array
    {
        try {
            return $this->get('admin/orders/'.$id, true)['data'] ?? null;
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw $e;
        }
    }

    public function adminUpdateOrderStatus(int $id, string $status): array
    {
        return $this->patch('admin/orders/'.$id.'/status', ['status' => $status], true)['data'] ?? [];
    }

    public function adminGetUsers(): array
    {
        return $this->get('admin/users', true)['data'] ?? [];
    }

    public function adminUpdateUser(int $id, array $payload): array
    {
        return $this->put('admin/users/'.$id, $payload, true)['data'] ?? [];
    }

    public function adminSetUserBlocked(int $id, bool $bloquer): array
    {
        return $this->patch('admin/users/'.$id.'/bloquer', ['bloquer' => $bloquer], true);
    }

    public function adminDeleteUser(int $id): void
    {
        $this->delete('admin/users/'.$id, true);
    }

    public function adminGetPromoCodes(): array
    {
        return $this->get('admin/promo-codes', true)['data'] ?? [];
    }

    public function adminCreatePromoCode(array $payload): array
    {
        return $this->post('admin/promo-codes', $payload, true)['data'] ?? [];
    }

    public function adminUpdatePromoCode(int $id, array $payload): array
    {
        return $this->put('admin/promo-codes/'.$id, $payload, true)['data'] ?? [];
    }

    public function adminDeletePromoCode(int $id): void
    {
        $this->delete('admin/promo-codes/'.$id, true);
    }

    public function adminUpdateSlides(array $slides): array
    {
        return $this->put('admin/homepage/slides', ['slides' => $slides], true)['data'] ?? [];
    }

    public function adminDeleteSlide(int $id): void
    {
        $this->delete('admin/homepage/slides/'.$id, true);
    }

    public function adminUpdateHomepageContent(string $text): array
    {
        return $this->put('admin/homepage/content', ['content_text' => $text], true)['data'] ?? [];
    }

    public function adminGetLogs(array $query = []): array
    {
        $path = 'admin/logs';
        if ($query !== []) {
            $path .= '?'.http_build_query($query);
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = max(1, (int) ($query['per_page'] ?? 50));
        $parsed = api_parse_paginated_response($this->get($path, true)['data'] ?? [], $perPage, $page);

        return array_merge($parsed['meta'], ['data' => $parsed['items']]);
    }

    public function adminGetAuditLogs(array $query = []): array
    {
        return $this->adminGetLogs($query);
    }

    public function adminGetContactMessages(array $query = []): array
    {
        $path = 'admin/contact-messages';
        if ($query !== []) {
            $path .= '?'.http_build_query($query);
        }

        $data = $this->get($path, true)['data'] ?? [];

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    public function adminReplyContactMessage(int $id, string $reply): array
    {
        return $this->post('admin/contact-messages/'.$id.'/reply', [
            'reply' => $reply,
        ], true);
    }

    public function adminUpdateContactStatus(int $id, string $status): array
    {
        return $this->patch('admin/contact-messages/'.$id.'/status', [
            'status' => $status,
        ], true);
    }

    /**
     * @param array{tmp_name:string,name:string,type?:string,error?:int} $file
     */
    public function adminUploadImage(array $file, string $folder = 'products'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            throw new RuntimeException('Fichier image invalide.');
        }

        if (! class_exists(CURLFile::class)) {
            throw new RuntimeException('Extension PHP curl requise pour l\'upload Cloudinary.');
        }

        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('cyna_session_start')) {
                cyna_session_start();
            } else {
                session_start();
            }
        }

        $token = $_SESSION['api_token'] ?? '';
        if ($token === '') {
            throw new RuntimeException('Non authentifié.');
        }

        $url = $this->baseUrl.'/admin/uploads/image';
        $mime = $file['type'] ?? mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
        $post = [
            'folder' => $folder,
            'image' => new CURLFile($file['tmp_name'], $mime, $file['name']),
        ];

        [$raw, $errno, $error, $status] = $this->execMultipart($url, $token, $post, API_SSL_VERIFY);

        $sslErrors = [35, 51, 53, 54, 58, 59, 60, 64, 66, 77, 83];
        if ($errno !== 0 && in_array($errno, $sslErrors, true)) {
            [$raw, $errno, $error, $status] = $this->execMultipart($url, $token, $post, false);
        }

        if ($errno !== 0) {
            throw new RuntimeException('Erreur upload cURL ('.$errno.'): '.$error);
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Réponse upload invalide (HTTP '.$status.')');
        }

        if ($status >= 400) {
            $message = $decoded['message'] ?? 'Erreur upload HTTP '.$status;
            if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                $first = reset($decoded['errors']);
                if (is_array($first) && isset($first[0])) {
                    $message = (string) $first[0];
                }
            }
            throw new RuntimeException($message);
        }

        $data = $decoded['data'] ?? $decoded;
        if (! is_array($data)) {
            throw new RuntimeException('Réponse upload invalide.');
        }

        $publicId = trim((string) ($data['public_id'] ?? ''));
        if ($publicId !== '') {
            $data['public_id'] = $publicId;
            $data['url'] = cloudinary_delivery_url($publicId) ?? trim((string) ($data['url'] ?? ''));
        }

        $url = trim((string) ($data['url'] ?? $data['secure_url'] ?? ''));
        if ($url === '' && $publicId === '') {
            throw new RuntimeException('Réponse upload sans URL Cloudinary.');
        }

        if ($url !== '') {
            $data['url'] = $url;
        }

        return $data;
    }

    /** @deprecated Utiliser adminGetContactMessages() */
    public function adminGetChatLogs(array $query = []): array
    {
        return $this->adminGetContactMessages($query);
    }

    private function storeAuthSession(array $response): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('cyna_session_start')) {
                cyna_session_start();
            } else {
                session_start();
            }
        }
        $user = $response['data']['user'] ?? [];
        $token = $response['data']['token'] ?? '';

        $_SESSION['api_token'] = $token;
        $_SESSION['utilisateur_id'] = $user['id'] ?? null;
        $_SESSION['utilisateur_prenom'] = $user['prenom'] ?? '';
        $_SESSION['utilisateur_nom'] = $user['nom'] ?? '';
        $_SESSION['utilisateur_email'] = $user['email'] ?? '';
        $_SESSION['is_admin'] = ! empty($user['is_admin']) ? 1 : 0;

        if (function_exists('cyna_session_apply_login_policy')) {
            cyna_session_apply_login_policy(! empty($user['is_admin']));
        }
    }

    private function request(string $method, string $path, ?array $body = null, bool $auth = false): array
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException('Extension PHP cURL non activée. Activez-la dans php.ini (extension=curl).');
        }

        $url = $this->baseUrl.'/'.ltrim($path, '/');

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($auth) {
            if (session_status() === PHP_SESSION_NONE) {
                if (function_exists('cyna_session_start')) {
                    cyna_session_start();
                } else {
                    session_start();
                }
            }
            $token = $_SESSION['api_token'] ?? '';
            if ($token === '') {
                throw new RuntimeException('Non authentifié.');
            }
            $headers[] = 'Authorization: Bearer '.$token;
        }

        [$raw, $errno, $error, $status] = $this->exec($url, $method, $headers, $body, API_SSL_VERIFY);

        $sslErrors = [35, 51, 53, 54, 58, 59, 60, 64, 66, 77, 83];
        if ($errno !== 0 && in_array($errno, $sslErrors, true)) {
            [$raw, $errno, $error, $status] = $this->exec($url, $method, $headers, $body, false);
        }

        if ($errno !== 0) {
            throw new RuntimeException('Erreur cURL ('.$errno.'): '.$error.' — URL: '.$url);
        }

        if ($raw === false || $raw === '') {
            throw new RuntimeException('Réponse vide de l\'API (HTTP '.$status.') — URL: '.$url);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Réponse JSON invalide (HTTP '.$status.'): '.substr($raw, 0, 200));
        }

        if ($status >= 400) {
            $message = $decoded['message'] ?? ($decoded['error'] ?? 'Erreur API HTTP '.$status);
            if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                $first = reset($decoded['errors']);
                if (is_array($first) && isset($first[0])) {
                    $message = (string) $first[0];
                }
            }
            throw new RuntimeException($message);
        }

        return $decoded;
    }

    private function exec(string $url, string $method, array $headers, ?array $body, bool $sslVerify): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$raw, $errno, $error, $status];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0:string|false,1:int,2:string,3:int}
     */
    private function execMultipart(string $url, string $token, array $post, bool $sslVerify): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer '.$token,
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$raw, $errno, $error, $status];
    }
}
