<?php

/*
 * Форма обратной связи (https://itchief.ru/lessons/php/feedback-form-for-website)
 * Copyright 2016-2023 Alexander Maltsev
 * Licensed under MIT (https://github.com/itchief/feedback-form/blob/master/LICENSE)
 */

header('Content-Type: application/json');

// обработка только ajax запросов (при других запросах завершаем выполнение скрипта)
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
  exit();
}

// обработка данных, посланных только методом POST (при остальных методах завершаем выполнение скрипта)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  exit();
}

// имя файла для хранения логов
define('LOG_FILE', 'logs/' . date('Y-m-d') . '.log');
// писать предупреждения и ошибки в лог
const HAS_WRITE_LOG = true;
// проверять ли капчу
const HAS_CHECK_CAPTCHA = true;
// обязательно ли наличие файлов, прикреплённых к форме
const HAS_ATTACH_REQUIRED = false;
// разрешённые mime типы файлов
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/gif', 'image/png'];
// максимально-допустимый размер файла
const MAX_FILE_SIZE = 512 * 1024;
// директория для хранения файлов
define('UPLOAD_PATH', dirname(__FILE__) . '/uploads/');

// отправлять письмо
const HAS_SEND_EMAIL = true;
// добавить ли прикреплённые файлы в тело письма в виде ссылок
const HAS_ATTACH_IN_BODY = true;
const EMAIL_SETTINGS = [
  'addresses' => ['Sales@kantdesign.ru'], // кому необходимо отправить письмо
  'from' => ['Sales@kantdesign.ru', 'kant-design.ru'], // от какого email и имени необходимо отправить письмо
  'subject' => 'Сообщение с формы обратной связи', // тема письма
  'host' => 'ssl://smtp.yandex.ru', // SMTP-хост
  'username' => 'Sales@kantdesign.ru', // // SMTP-пользователь
  'password' => 'nrmvbxtthiskbpbi', // SMTP-пароль
  'port' => '465' // SMTP-порт
];
const HAS_SEND_NOTIFICATION = false;
const BASE_URL = 'https://kant-design.ru/';
const SUBJECT_FOR_CLIENT = 'Ваше сообщение доставлено';
//
const HAS_WRITE_TXT = true;

function itc_log($message)
{
  if (HAS_WRITE_LOG) {
    error_log('Date:  ' . date('d.m.Y h:i:s') . '  |  ' . $message . PHP_EOL, 3, LOG_FILE);
  }
}

$data = [
  'errors' => [],
  'form' => [],
  'logs' => [],
  'result' => 'success'
];

$attachs = [];

/* 4 ЭТАП - ВАЛИДАЦИЯ ДАННЫХ (ЗНАЧЕНИЙ ПОЛЕЙ ФОРМЫ) */

// валидация name
if (!empty($_POST['name'])) {
  $data['form']['name'] = htmlspecialchars($_POST['name']);
} else {
  $data['result'] = 'error';
  $data['errors']['name'] = 'Заполните это поле.';
  itc_log('Не заполнено поле name.');
}



/*Валидация Phone */
if (!empty($_POST['phone'])) {
  $data['form']['phone'] = preg_replace('/D/', '', $_POST['phone']);
}
// валидация message
if (isset($_POST['message'])) {
  $data['form']['message'] = htmlspecialchars($_POST['message']);
} 

// валидация agree
if ($_POST['agree'] == 'true') {
  $data['form']['agree'] = true;
} else {
  $data['result'] = 'error';
  $data['errors']['agree'] = 'Необходимо установить этот флажок.';
  itc_log('Не установлен флажок для поля agree.');
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

if ($data['result'] == 'success' && HAS_SEND_EMAIL == true) {
  // получаем содержимое email шаблона и заменяем в нём 
  $template = file_get_contents(dirname(__FILE__) . '/template/email.tpl');
  $search = ['%subject%', '%name%', '%message%', '%phone%', '%date%'];
  $replace = [EMAIL_SETTINGS['subject'], $data['form']['name'], $data['form']['message'], $data['form']['phone'], date('d.m.Y H:i')];
  $body = str_replace($search, $replace, $template);
  // добавление файлов в виде ссылок
  if (HAS_ATTACH_IN_BODY && count($attachs)) {
    $ul = 'Файлы, прикреплённые к форме:<ul>';
    foreach ($attachs as $attach) {
      $href = str_replace($_SERVER['DOCUMENT_ROOT'], '', $attach);
      $name = basename($href);
      $ul .= '<li><a href="' . BASE_URL . $href . '">' . $name . '</a></li>';

      $data['href'][] = BASE_URL . $href;
    }
    $ul .= '</ul>';
    $body = str_replace('%attachs%', $ul, $body);
  } else {
    $body = str_replace('%attachs%', '', $body);
  }
  $mail = new PHPMailer(true);
  $mail->SMTPDebug = 2;
  $mail->Debugoutput = function($str, $level) {
    $file = __DIR__ . '/logs/smtp_' . date('Y-m-d') . '.log';
    file_put_contents($file, gmdate('Y-m-d H:i:s'). "\t$level\t$str\n", FILE_APPEND | LOCK_EX);
  };
  try {
    //Server settings
    $mail->isSMTP();
    $mail->Host = EMAIL_SETTINGS['host'];
    $mail->SMTPAuth = true;
    $mail->Username = EMAIL_SETTINGS['username'];
    $mail->Password = EMAIL_SETTINGS['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = EMAIL_SETTINGS['port'];
    //Recipients
    $mail->setFrom(EMAIL_SETTINGS['from'][0], EMAIL_SETTINGS['from'][1]);
    foreach (EMAIL_SETTINGS['addresses'] as $address) {
      $mail->addAddress(trim($address));
    }
    //Attachments
    if (!HAS_ATTACH_IN_BODY && count($attachs)) {
      foreach ($attachs as $attach) {
        $mail->addAttachment($attach);
      }
    }
    //Content
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isHTML(true);
    $mail->Subject = EMAIL_SETTINGS['subject'];
    $mail->Body = $body;
    $mail->send();
    itc_log('Форма успешно отправлена.');
  } catch (Exception $e) {
    $data['result'] = 'error';
    itc_log('Ошибка при отправке письма: ' . $mail->ErrorInfo);
  }
}

if ($data['result'] == 'success' && HAS_SEND_NOTIFICATION) {
  // очистка адресов и прикреплёных файлов
  $mail->clearAllRecipients();
  $mail->clearAttachments();
  // получаем содержимое email шаблона и заменяем в нём плейсхолдеры на соответствующие им значения
  $template = file_get_contents(dirname(__FILE__) . '/template/email_client.tpl');
  $search = ['%subject%', '%name%', '%date%'];
  $replace = [SUBJECT_FOR_CLIENT, $data['form']['name'], date('d.m.Y H:i')];
  $body = str_replace($search, $replace, $template);
  try {
    // устанавливаем параметры
    $mail->Subject = SUBJECT_FOR_CLIENT;
    $mail->Body = $body;
    $mail->addAddress($data['form']['email']);
    $mail->send();
    itc_log('Успешно отправлено уведомление пользователю.');
  } catch (Exception $e) {
    itc_log('Ошибка при отправке уведомления пользователю: ' . $mail->ErrorInfo);
  }
}

if ($data['result'] == 'success' && HAS_WRITE_TXT) {
  $output = '=======' . date('d.m.Y H:i') . '=======';
  $output .= 'Имя: ' . $data['form']['name'] . PHP_EOL;
  $output .= 'Сообщение: ' . $data['form']['message'] . PHP_EOL;
  $output .= 'Телефон: ' . isset($data['form']['phone']) ? $data['form']['phone'] : 'не указан' . PHP_EOL;
  if (count($attachs)) {
    $output .= 'Файлы:' . PHP_EOL;
    foreach ($attachs as $attach) {
      $output .= $attach . PHP_EOL;
    }
  }
  $output = '=====================';
  error_log($output, 3, 'logs/forms.log');
}

echo json_encode($data);
exit();
