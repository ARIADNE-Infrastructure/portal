<?php

namespace Application;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Contact {
  private $settings;
  private $logger;

  public function __construct ($settings, $logger) {
    $this->settings = $settings;
    $this->logger = $logger;
  }

  /**
   * Process, validate and send mail
   */
  public function sendMail () {
    $ok = false;
    $error = '';

    $fromName = strip_tags(trim($_POST['name'] ?? ''));
    $message = strip_tags(trim($_POST['message'] ?? ''));
    $captcha = strip_tags(trim($_POST['captcha'] ?? ''));

    $subject = strip_tags(trim($_POST['subject'] ?? ''));
    $subject = 'Ariadne Portal - ' . str_replace(["\n", "\r", ':'], '', $subject);
    if (!$subject) {
      $subject = 'Mail from Ariadne Portal';
    }

    $fromEmail = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $fromName = str_replace(["\n", "\r", ':'], '', $fromName);

    if (!$fromName) {
      $error = 'Please enter your name';

    } else if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email';

    } else if (!$message) {
      $error = 'Please enter a message';

    } else if (!$captcha || !$this->validCaptcha($captcha)) {
      $error = 'Invalid captcha. Reload and try again';

    } else {
      $mailResult = $this->doSendMail($fromName, $fromEmail, $subject, $message);
      $ok = $mailResult['ok'];
      $error = $mailResult['error'];
    }

    return [
      'ok' => $ok,
      'error' => $error,
    ];

  }

  /**
   * Validate Captcha
   */
  private function validCaptcha ($captcha) {
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
      'secret' => $this->settings->environment->contact->captchaSecret,
      'response' => $captcha,
    ]);

    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $res && !empty($res['success']);
  }



  /**
   * Send mail through relay
   */
  private function doSendMail ($fromName, $fromEmail, $subject, $message) {
    $SMTP_HOST      = getenv('SMTP_HOST');
    $SMTP_PORT      = getenv('SMTP_PORT');
    $SMTP_CHANNEL   = getenv('SMTP_CHANNEL');
    $SMTP_USER      = getenv('SMTP_USER');
    $SMTP_PASS      = getenv('SMTP_PASS');

    if (!$SMTP_HOST || !$SMTP_PORT || !$SMTP_CHANNEL || !$SMTP_USER || !$SMTP_PASS ) {

      if ($this->settings->environment->contact->debugMode) {
        $this->logger->info('ERROR: Missing mandatory environment settings in mail system!');
      }

      return [
        'ok' => false,
        'error' => 'ERROR: Missing mandatory settings in mail system!',
      ];
    }

    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
      //Server settings
      // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                // Enable verbose debug output
      $mail->isSMTP();                                      // Send using SMTP
      $mail->Host       = $SMTP_HOST;                       // Set the SMTP server to send through
      $mail->SMTPAuth   = true;                             // Enable SMTP authentication
      $mail->Username   = $SMTP_USER;                       // SMTP username
      $mail->Password   = $SMTP_PASS;                       // SMTP password
      //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
      $mail->Port       = $SMTP_PORT;                       // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
      $mail->CharSet = 'UTF-8';

      //Recipients
      $mail->setFrom($fromEmail, 'Ariadne Portal');
      $mail->addReplyTo($fromEmail, $fromName);
      $mail->addAddress($this->settings->environment->contact->email, $fromName);
      $mail->addCC($fromEmail);

      // Content
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $message;
      //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

      $mail->send();

      return [
        'ok' => true,
        'error' => '',
      ];

    } catch (Exception $e) {
      if ($this->settings->environment->contact->debugMode) {
        $this->logger->info($e);
      }
      return [
        'ok' => false,
        'error' => $mail->ErrorInfo,
      ];
    }
  }
}
