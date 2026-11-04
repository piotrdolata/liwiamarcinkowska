<?php
// Włącz proste raportowanie błędów do debugowania lokalnego w razie potrzeby (wersja produkcyjna powinna mieć to wyłączone)
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Definicja adresu odbiorcy
$to = 'dolata.piotrek@gmail.com';
$subject = 'Nowa wiadomość z formularza na stronie';

// Odbiór i czyszczenie danych
$name = isset($_POST['Name']) ? htmlspecialchars(strip_tags(trim($_POST['Name']))) : '';
$email = isset($_POST['Email']) ? filter_var(trim($_POST['Email']), FILTER_SANITIZE_EMAIL) : '';
$phone = isset($_POST['Phone']) ? htmlspecialchars(strip_tags(trim($_POST['Phone']))) : '';
$honeypot = isset($_POST['website_url']) ? $_POST['website_url'] : '';

// 1. Zabezpieczenie Honeypot
// Jeśli pole jest wypełnione, najprawdopodobniej to bot.
if (!empty($honeypot)) {
    // Odpowiadamy, jakby to był sukces, żeby zmylić bota, ale nie wysyłamy maila.
    echo json_encode(['status' => 'success', 'message' => 'Wiadomość została wysłana.']);
    exit;
}

// 2. Prosta walidacja
if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Proszę wypełnić wszystkie wymagane pola (Imię i Email).']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Niepoprawny format adresu email.']);
    exit;
}

// 3. Budowa wiadomości
$message = "Nowa wiadomość z formularza kontaktowego:\n\n";
$message .= "Imię: " . $name . "\n";
$message .= "Email: " . $email . "\n";
$message .= "Telefon: " . $phone . "\n";

// Nagłówki email
// Zmieniłem From na standardowe (albo serwer docelowy może uznać za spoofing i odrzucić).
// Najlepiej wysyłać maile Z SAMEJ DOMENY serwera, ale tutaj dla prostoty użyjemy adresu zwrotnego.
$headers = "From: webmaster@" . $_SERVER['SERVER_NAME'] . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// 4. Wysyłka wiadomości
if (mail($to, $subject, $message, $headers)) {
    echo json_encode(['status' => 'success', 'message' => 'Dziękujemy! Twoja wiadomość została wysłana. Odpowiem w ciągu 24 godzin.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Wystąpił problem z wysłaniem wiadomości. Spróbuj ponownie później.']);
}
?>