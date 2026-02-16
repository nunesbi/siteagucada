<?php
date_default_timezone_set('America/Sao_Paulo');

function clean($v) {
  return trim(str_replace(["\r", "\n"], " ", (string)$v));
}

function log_msg($msg) {
  file_put_contents(__DIR__ . "/contato.log",
    "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n",
    FILE_APPEND
  );
}

function smtp_read($fp) {
  $data = "";
  while (!feof($fp)) {
    $line = fgets($fp, 515);
    if ($line === false) break;
    $data .= $line;

    if (preg_match('/^\d{3}\s/', $line)) break;
  }
  return $data;
}

function smtp_expect($fp, $expected_code, $context) {
  $resp = smtp_read($fp);
  $code = intval(substr($resp, 0, 3));
  if ($code !== $expected_code) {
    log_msg("SMTP FAIL ($context): expected $expected_code, got $code | resp=" . trim($resp));
    return false;
  }
  log_msg("SMTP OK ($context): " . trim(str_replace("\r\n"," | ",$resp)));
  return true;
}

function smtp_send($fp, $cmd, $expected_code, $context) {
  fwrite($fp, $cmd . "\r\n");
  return smtp_expect($fp, $expected_code, $context . " => " . $cmd);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /");
  exit;
}

$nome     = clean($_POST["name"] ?? "");
$email    = clean($_POST["email"] ?? "");
$telefone = clean($_POST["phone"] ?? "");
$assunto  = clean($_POST["subject"] ?? "");
$mensagem = trim((string)($_POST["message"] ?? ""));

log_msg("POST recebido | nome={$nome} | email={$email} | assunto={$assunto}");

if ($nome === "" || $email === "" || $assunto === "" || $mensagem === "") {
  log_msg("ERRO: campos faltando");
  header("Location: /pages/contato.html?erro=1");
exit;
exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  log_msg("ERRO: e-mail inválido");
  header("Location: /pages/contato.html?erro=1");
exit;
exit;
}

$SMTP_HOST = "smtp.hostinger.com";
$SMTP_PORT = 465;

$FROM_EMAIL = "diretoria@atleticaagucada.com.br";
$FROM_NAME  = "Site Aguçada";
$TO_EMAIL   = "diretoria@atleticaagucada.com.br";

$SMTP_USER = $FROM_EMAIL;
$SMTP_PASS = "?"; 


$subject = $assunto;
$body  = "Novo contato pelo site (A.A.A.E AGUÇADA)\r\n\r\n";
$body .= "Nome: {$nome}\r\n";
$body .= "E-mail: {$email}\r\n";
if ($telefone !== "") $body .= "Telefone: {$telefone}\r\n";
$body .= "\r\nMensagem:\r\n{$mensagem}\r\n";

$headers  = "From: {$FROM_NAME} <{$FROM_EMAIL}>\r\n";
$headers .= "Reply-To: {$nome} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Subject: {$subject}\r\n";
$headers .= "Date: " . date(DATE_RFC2822) . "\r\n";


$data = $headers . "\r\n" . $body;
$data = preg_replace("/\r\n\.\r\n/", "\r\n..\r\n", $data); 

$fp = @fsockopen("ssl://{$SMTP_HOST}", $SMTP_PORT, $errno, $errstr, 30);
if (!$fp) {
  log_msg("ERRO: conexão SMTP falhou errno={$errno} err={$errstr}");
  header("Location: /pages/contato.html?erro=1");
exit;
exit;
}
stream_set_timeout($fp, 30);


if (!smtp_expect($fp, 220, "BANNER")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }


if (!smtp_send($fp, "EHLO atleticaagucada.com.br", 250, "EHLO")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }


if (!smtp_send($fp, "AUTH LOGIN", 334, "AUTH LOGIN")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }
if (!smtp_send($fp, base64_encode($SMTP_USER), 334, "AUTH USER")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }
if (!smtp_send($fp, base64_encode($SMTP_PASS), 235, "AUTH PASS")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }


if (!smtp_send($fp, "MAIL FROM:<{$FROM_EMAIL}>", 250, "MAIL FROM")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }
if (!smtp_send($fp, "RCPT TO:<{$TO_EMAIL}>", 250, "RCPT TO")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }


if (!smtp_send($fp, "DATA", 354, "DATA")) { fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }

fwrite($fp, $data . "\r\n.\r\n");
if (!smtp_expect($fp, 250, "DATA END")) { smtp_send($fp, "QUIT", 221, "QUIT"); fclose($fp); header("Location: /pages/contato.html?erro=1");
exit;
exit; }

smtp_send($fp, "QUIT", 221, "QUIT");
fclose($fp);

log_msg("ENVIADO OK");
header("Location: /pages/contato.html?sucesso=1");
exit;
?>
