<?php
/**
 * ============================================================================
 * DYT - DESIGN YOUR TEXTILE — Project Enquiry Form Backend (contact.php)
 * ============================================================================
 * First-party PHP endpoint for the DYT - Design Your Textile contact form.
 * Hosted on the same Hostinger server as the website. No third-party form
 * service (no EmailJS, Formspree, Google Forms, etc.) is used.
 *
 * Compatible with normal Hostinger shared hosting (PHP 7.4+ / 8.x) using the
 * built-in mail() function. If your host requires SMTP authentication, see
 * the OPTIONAL SMTP section near the bottom — credentials stay server-side.
 *
 * ----------------------------------------------------------------------------
 *  ▶ CONFIGURE THIS:  set your business email address below.
 * ----------------------------------------------------------------------------
 */

/* ==========================================================================
 * 1. CONFIGURATION  —  edit ONLY this block
 * ========================================================================== */

/**
 * Recipient email address. This is the only place you need to change it.
 * This is the ONLY point of contact used across the DYT website.
 */
$DYT_RECIPIENT_EMAIL = 'admin@designyourtextile.com';

/**
 * "From" address used as the envelope sender.
 * IMPORTANT: On shared hosting this MUST be an address on your own domain
 * (Hostinger will reject or spam-flag mail sent "from" an external domain).
 * The customer's email is used as Reply-To, never as the From address.
 */
$DYT_FROM_EMAIL = 'no-reply@designyourtextile.com';

/**
 * Friendly From name shown in the inbox.
 */
$DYT_FROM_NAME = 'DYT - Design Your Textile';

/**
 * Subject line prefix for enquiry emails.
 */
$DYT_SUBJECT_PREFIX = 'New Textile Project Enquiry';

/**
 * Absolute URL of the site (used for the email header/logo text only).
 */
$DYT_SITE_URL = 'https://designyourtextile.com';

/**
 * Maximum accepted upload size, in bytes (10 MB).
 * Keep this at or below your Hostinger PHP upload_max_filesize / post_max_size.
 */
$DYT_MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

/**
 * Allowed upload extensions (lowercase, no dot).
 */
$DYT_ALLOWED_EXT = array('pdf', 'ai', 'psd', 'png', 'jpg', 'jpeg', 'webp', 'zip', 'rar', 'tif', 'tiff');

/**
 * Directory (relative to this script) where uploads are temporarily stored
 * before being attached to the email. Created automatically if missing.
 */
$DYT_UPLOAD_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'dyt-uploads';

/**
 * Minimum number of seconds a human is expected to spend filling the form.
 * Submissions faster than this are treated as bot traffic.
 */
$DYT_MIN_FILL_SECONDS = 3;

/**
 * Maximum form age in seconds before it is considered stale/expired.
 */
$DYT_MAX_FORM_AGE = 60 * 60 * 6; // 6 hours

/**
 * Simple per-IP rate limit: max submissions allowed within the window.
 */
$DYT_RATE_LIMIT_MAX    = 5;
$DYT_RATE_LIMIT_WINDOW = 900; // 15 minutes

/* ==========================================================================
 * 2. BOOTSTRAP
 * ========================================================================== */

// Only accept POST.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    dyt_respond(false, 'This endpoint only accepts form submissions.', 405);
}

// JSON response helper is defined below (function declarations are hoisted).
function dyt_respond($success, $message, $httpCode = 200) {
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
    }
    echo json_encode(array(
        'success' => (bool) $success,
        'message' => (string) $message,
    ));
    exit;
}

// Basic fetch/XHR check — blocks blind form-spam scripts that don't set it.
// (Not a security boundary on its own; used alongside honeypot + timing.)
if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
    dyt_respond(false, 'Invalid request. Please submit the form from the website.', 400);
}

// Reject oversized POST bodies early (covers multi-file abuse).
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > ($DYT_MAX_UPLOAD_BYTES + 1024 * 512)) {
    dyt_respond(false, 'The submitted data is too large. Please reduce the attachment size.', 413);
}

/* ==========================================================================
 * 3. INPUT COLLECTION + SANITIZATION
 * ========================================================================== */

function dyt_clean($value, $maxLen = 500) {
    if (is_array($value)) {
        return '';
    }
    $value = (string) $value;
    // Remove control chars except tab/newline, strip tags, trim.
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    $value = strip_tags($value);
    $value = trim($value);
    if (function_exists('mb_substr')) {
        $value = mb_substr($value, 0, $maxLen, 'UTF-8');
    } else {
        $value = substr($value, 0, $maxLen);
    }
    return $value;
}

function dyt_header_safe($value) {
    // Prevent header injection via CR/LF and angle-bracket/quote breakout.
    $value = str_replace(array("\r", "\n", "%0a", "%0d"), '', (string) $value);
    $value = str_replace(array('<', '>', '"'), '', $value);
    return trim($value);
}

$name         = dyt_clean($_POST['name'] ?? '', 120);
$email        = dyt_clean($_POST['email'] ?? '', 160);
$phone        = dyt_clean($_POST['phone'] ?? '', 40);
$company      = dyt_clean($_POST['company'] ?? '', 160);
$projectType  = dyt_clean($_POST['project_type'] ?? '', 160);
$timeline     = dyt_clean($_POST['timeline'] ?? '', 120);
$message      = dyt_clean($_POST['message'] ?? '', 4000);
$honeypot     = dyt_clean($_POST['company_url'] ?? '', 200);
$formStarted  = dyt_clean($_POST['form_started'] ?? '', 20);
$formToken    = dyt_clean($_POST['form_token'] ?? '', 64);

/* ==========================================================================
 * 4. SPAM / ABUSE PROTECTION
 * ========================================================================== */

// 4a. Honeypot — real users never see or fill this field.
if ($honeypot !== '') {
    // Respond with a generic success so bots don't learn they were caught.
    dyt_respond(true, 'Thank you for your enquiry.');
}

// 4b. Time trap — reject impossibly fast or stale submissions.
if ($formStarted === '' || !ctype_digit($formStarted)) {
    dyt_respond(false, 'Your session expired. Please reload the page and try again.', 400);
}
$elapsed = time() - ((int) $formStarted / 1000);
if ($elapsed < $DYT_MIN_FILL_SECONDS) {
    dyt_respond(false, 'Your submission came through too quickly. Please try again.', 429);
}
if ($elapsed > $DYT_MAX_FORM_AGE) {
    dyt_respond(false, 'This form has expired. Please reload the page and submit again.', 400);
}

// 4c. Token presence check.
if ($formToken === '' || strlen($formToken) < 6) {
    dyt_respond(false, 'Invalid submission token. Please reload the page and try again.', 400);
}

// 4d. Link-flood heuristic — block messages stuffed with many URLs.
$linkCount = preg_match_all('#https?://#i', $message);
if ($linkCount > 4) {
    dyt_respond(false, 'Your message contains too many links. Please simplify and try again.', 422);
}

// 4e. Simple file-based per-IP rate limiting.
dyt_rate_limit($DYT_RATE_LIMIT_MAX, $DYT_RATE_LIMIT_WINDOW);

function dyt_rate_limit($max, $window) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dyt-rate';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . 'rl_' . md5($ip) . '.json';
    $now = time();
    $hits = array();
    if (is_readable($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $ts) {
                    if (($now - (int) $ts) < $window) {
                        $hits[] = (int) $ts;
                    }
                }
            }
        }
    }
    if (count($hits) >= $max) {
        dyt_respond(false, 'Too many submissions from your connection. Please try again later.', 429);
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
}

/* ==========================================================================
 * 5. SERVER-SIDE VALIDATION
 * ========================================================================== */

$errors = array();

if ($name === '' || strlen($name) < 2) {
    $errors[] = 'Please provide your name.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}
if ($phone === '' || strlen(preg_replace('/\D/', '', $phone)) < 6) {
    $errors[] = 'Please provide a valid phone or WhatsApp number.';
}
if ($projectType === '') {
    $errors[] = 'Please select a project type.';
}
if ($message === '' || strlen($message) < 10) {
    $errors[] = 'Please include a message with at least a short project description.';
}

// Guard against header injection in the Reply-To address.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = '';
}

if (!empty($errors)) {
    dyt_respond(false, implode(' ', $errors), 422);
}

/* ==========================================================================
 * 6. SAFE FILE UPLOAD HANDLING
 * ========================================================================== */

$attachmentPath = null;
$attachmentName = null;

if (isset($_FILES['attachment']) && is_array($_FILES['attachment'])) {
    $file = $_FILES['attachment'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            dyt_respond(false, 'The file upload failed (error code ' . (int) $file['error'] . '). Please try again.', 422);
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            dyt_respond(false, 'Invalid upload detected.', 422);
        }

        if ((int) $file['size'] <= 0 || (int) $file['size'] > $DYT_MAX_UPLOAD_BYTES) {
            dyt_respond(false, 'The attached file exceeds the 10 MB limit.', 422);
        }

        $originalName = (string) ($file['name'] ?? 'attachment');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $DYT_ALLOWED_EXT, true)) {
            dyt_respond(false, 'That file type is not allowed. Allowed: ' . strtoupper(implode(', ', $DYT_ALLOWED_EXT)) . '.', 422);
        }

        // Create the upload directory (deny direct web access via .htaccess).
        if (!is_dir($DYT_UPLOAD_DIR)) {
            if (!@mkdir($DYT_UPLOAD_DIR, 0755, true)) {
                dyt_respond(false, 'Server storage is unavailable. Please try again later.', 500);
            }
        }
        $htaccess = $DYT_UPLOAD_DIR . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }

        // Build a safe, unique stored filename. Never trust the client name.
        $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = substr($safeBase, 0, 60);
        if ($safeBase === '') {
            $safeBase = 'attachment';
        }
        $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $ext;
        $destination = $DYT_UPLOAD_DIR . DIRECTORY_SEPARATOR . $storedName;

        if (!@move_uploaded_file($file['tmp_name'], $destination)) {
            dyt_respond(false, 'We could not store your attachment. Please try again.', 500);
        }
        @chmod($destination, 0644);

        $attachmentPath = $destination;
        $attachmentName = $originalName;
    }
}

/* ==========================================================================
 * 7. BUILD & SEND THE EMAIL
 * ========================================================================== */

$safeSubjectName = dyt_header_safe($company !== '' ? $company : $name);
$subject = $DYT_SUBJECT_PREFIX . ' — ' . $safeSubjectName;

$lines = array(
    'New textile project enquiry received from the DYT - Design Your Textile website.',
    str_repeat('=', 60),
    '',
    'Name:              ' . $name,
    'Email:             ' . $email,
    'Phone / WhatsApp:  ' . $phone,
    'Company / Brand:   ' . ($company !== '' ? $company : '—'),
    'Project Type:      ' . $projectType,
    'Timeline:          ' . ($timeline !== '' ? $timeline : '—'),
    '',
    'Message:',
    str_repeat('-', 60),
    $message,
    str_repeat('-', 60),
    '',
    'Submitted: ' . date('Y-m-d H:i:s T'),
    'IP:        ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    'Site:      ' . $DYT_SITE_URL,
);

if ($attachmentName !== null) {
    $lines[] = 'Attachment: ' . $attachmentName;
}

$body = implode("\r\n", $lines);

// Build MIME headers. Use Reply-To = customer, From = your own domain.
$boundary = 'dyt_' . bin2hex(random_bytes(12));

$headers = array();
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'From: ' . dyt_header_safe($DYT_FROM_NAME) . ' <' . dyt_header_safe($DYT_FROM_EMAIL) . '>';
$headers[] = 'Reply-To: ' . dyt_header_safe($name) . ' <' . dyt_header_safe($email) . '>';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$bodyFinal = $body;

if ($attachmentPath !== null && is_readable($attachmentPath)) {
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $fileData = chunk_split(base64_encode((string) file_get_contents($attachmentPath)));
    $mimeType = dyt_mime_for_ext(pathinfo($attachmentPath, PATHINFO_EXTENSION));
    $safeAttachName = dyt_header_safe($attachmentName);

    $bodyFinal  = "--{$boundary}\r\n";
    $bodyFinal .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $bodyFinal .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $bodyFinal .= $body . "\r\n\r\n";
    $bodyFinal .= "--{$boundary}\r\n";
    $bodyFinal .= "Content-Type: {$mimeType}; name=\"{$safeAttachName}\"\r\n";
    $bodyFinal .= "Content-Transfer-Encoding: base64\r\n";
    $bodyFinal .= "Content-Disposition: attachment; filename=\"{$safeAttachName}\"\r\n\r\n";
    $bodyFinal .= $fileData . "\r\n";
    $bodyFinal .= "--{$boundary}--";
} else {
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
}

$sendHeaders = implode("\r\n", $headers);
$sent = @mail($DYT_RECIPIENT_EMAIL, $subject, $bodyFinal, $sendHeaders, '-f' . $DYT_FROM_EMAIL);

// Clean up the temporary upload regardless of outcome.
if ($attachmentPath !== null && file_exists($attachmentPath)) {
    @unlink($attachmentPath);
}

if (!$sent) {
    dyt_respond(false, 'The server could not send your email right now. Please try again, or email ' . $DYT_RECIPIENT_EMAIL . ' directly.', 500);
}

dyt_respond(true, 'DYT - Design Your Textile will review your project details and reply within one business day.');

/* ==========================================================================
 * HELPERS
 * ========================================================================== */

function dyt_mime_for_ext($ext) {
    $map = array(
        'pdf'  => 'application/pdf',
        'zip'  => 'application/zip',
        'rar'  => 'application/vnd.rar',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',
        'ai'   => 'application/postscript',
        'psd'  => 'image/vnd.adobe.photoshop',
    );
    $ext = strtolower((string) $ext);
    return $map[$ext] ?? 'application/octet-stream';
}

/* ==========================================================================
 * OPTIONAL: SMTP AUTHENTICATION
 * --------------------------------------------------------------------------
 * Hostinger's mail() usually works out of the box. If your mailbox requires
 * authenticated SMTP (recommended for reliable deliverability), add a mailer
 * such as PHPMailer via Composer and replace the mail() call above. Keep ALL
 * credentials in a server-side file that is NOT web-accessible, e.g.:
 *
 *     $DYT_SMTP_HOST = 'smtp.hostinger.com';
 *     $DYT_SMTP_USER = 'no-reply@designyourtextile.com';
 *     $DYT_SMTP_PASS = '********';   // never expose this to the browser
 *     $DYT_SMTP_PORT = 465;
 *
 * These values must NEVER be sent to or referenced by frontend JavaScript.
 * ==========================================================================
 */
