<?php

namespace Application;

use Elastic\AppSettings;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Application\AppLogger;

class Contact {

  /**
   * Process, validate and send mail
   */
  public static function sendMail () {

    $settings = (new AppSettings())->getSettings();
    $ok = false;
    $error = '';

    $fromName = strip_tags(trim($_POST['name'] ?? ''));
    $message = strip_tags(trim($_POST['message'] ?? ''));
    $captcha = strip_tags(trim($_POST['captcha'] ?? ''));

    $subject = strip_tags(trim($_POST['subject'] ?? ''));
    $subject = str_replace(["\n", "\r", ':'], '', $subject);
    if (!$subject) {
      $subject = 'Mail from the Ariadne portal';
    }

    $fromEmail = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $fromName = str_replace(["\n", "\r", ':'], '', $fromName);

    if (!$fromName) {
      $error = 'Please enter your name';

    } else if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email';

    } else if (!$message) {
      $error = 'Please enter a message';

    } else if (!$captcha || !self::validCaptcha($settings, $captcha)) {
      $error = 'Invalid captcha. Reload and try again';

    } else {

      $mailResult = self::doSendMail($fromName, $fromEmail, $subject, $message, $settings );
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
  public static function validCaptcha ($settings, $captcha) {
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
      'secret' => $settings->environment->contact->captchaSecret,
      'response' => $captcha,
    ]);

    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $res && !empty($res['success']);
  }



  /**
   * Send mail through relay
   */
  private static function doSendMail($fromName, $fromEmail, $subject, $message, $settings) {


    $appLogger = new AppLogger();

    $SMTP_HOST      = getenv('SMTP_HOST');
    $SMTP_PORT      = getenv('SMTP_PORT');
    $SMTP_CHANNEL   = getenv('SMTP_CHANNEL');
    $SMTP_USER      = getenv('SMTP_USER');
    $SMTP_PASS      = getenv('SMTP_PASS');

    if(!$SMTP_HOST || !$SMTP_PORT || !$SMTP_CHANNEL || !$SMTP_USER || !$SMTP_PASS ) {

      if($settings->environment->debugMode) {
        $appLogger->info( 'ERROR: Missing mandatory environment settings in mail system!' );
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
        $mail->addAddress($settings->environment->contact->email, $fromName);               
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

        if($settings->environment->debugMode) {
          $appLogger->info( $e );
        }
        return [
          'ok' => false,
          'error' => $mail->ErrorInfo,
        ];
    }

  }
  
}
