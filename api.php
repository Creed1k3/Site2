<?php
/**
* Базовая конфигурация
*/
// * Апи ключ вашего акканута
$apiKey = 'f6930a1d15826a9aa10662dfca43758d';
// * Домен проекта на который происходит отправка заказов
$domain = 'shakes.pro';
// Урл оригинального лендинга, необходим для корректного расчета Вашей статистики
$landingUrl = 'http://land.com';
// * Идентификатор оффера на который вы льете
$offerId = '18448';
// Код потока заведенного в системе, если указан, статистика будет записываться на данный поток
$streamCode = '';
// Страница, отдаваемая при успешном заказе
$successPage = 'success.php';
// Страница, отдаваемая в случае ошибки
$errorPage = 'index.php';

/** --------- ЕДИНЫЙ CSV-ЛОГ ---------
 * формат: YYYY-MM-DD HH:MM:SS,"имя",телефон,sub1
 */
function writeLeadCsvLog($name, $phone, $sub1) {
    $dt   = date('Y-m-d H:i:s');
    $name = str_replace('"', '""', (string)$name);
    $line = $dt . ',"' . $name . '",' . (string)$phone . ',' . (string)$sub1 . PHP_EOL;
    file_put_contents(__DIR__ . '/leads.log', $line, FILE_APPEND | LOCK_EX);
}

/**
* Формирование отправляемого заказа
*/
$url = "http://$domain?r=/api/order/in&key=$apiKey";
$order = [
    'countryCode' => (!empty($_POST['country']) ? $_POST['country'] : ($_GET['country'] ? $_GET['country'] : 'RU')),
    'comment'     => (!empty($_POST['comment']) ? $_POST['comment'] : ($_GET['comment'] ? $_GET['comment'] : '')),
    'createdAt'   => date('Y-m-d H:i:s'),
    'ip'          => (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null), // ip пользователя
    'landingUrl'  => $landingUrl,
    'name'        => (!empty($_POST['name']) ? $_POST['name'] : ($_GET['name'] ? $_GET['name'] : '')),
    'offerId'     => $offerId,
    'phone'       => (!empty($_POST['phone']) ? $_POST['phone'] : ($_GET['phone'] ? $_GET['phone'] : '')),
    'referrer'    => (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null),
    'streamCode'  => $streamCode,
    'sub1'        => (!empty($_POST['sub1']) ? $_POST['sub1'] : ($_GET['sub1'] ? $_GET['sub1'] : '')),
    'sub2'        => (!empty($_POST['sub2']) ? $_GET['sub2'] : ''),
    'sub3'        => (!empty($_POST['sub3']) ? $_POST['sub3'] : ''),
    'sub4'        => (!empty($_POST['sub4']) ? $_POST['sub4'] : ''),
    'userAgent'   => (!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-'),
];

/** ЛОГИРУЕМ ЗАЯВКУ (только: дата-время,"имя",телефон,sub1) */
writeLeadCsvLog($order['name'] ?? '', $order['phone'] ?? '', $order['sub1'] ?? '');

/**
* Отправка заказа
*/
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $order);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, 'curl/' . (curl_version()['version'] ?? '7'));

try {
    $responseBody = curl_exec($curl);

    @file_put_contents(
        __DIR__ . '/shakes.response.log',
        date('Y.m.d H:i:s') . PHP_EOL . $responseBody,
        FILE_APPEND
    );

    if (empty($responseBody)) {
        throw new Exception('Error: Empty response for order. ' . var_export($order, true));
    }

    $response = json_decode($responseBody, true);
    if (empty($response)) {
        throw new Exception('Error: Broken json format for order. ' . PHP_EOL . var_export($order, true));
    }
    if ($response['status'] !== 'ok') {
        throw new Exception(
            'Error: Order is not accepted. '
            . PHP_EOL . 'Order: ' . var_export($order, true)
            . PHP_EOL . 'Response: ' . var_export($response, true)
        );
    }

    @file_put_contents(
        __DIR__ . '/order.success.log',
        date('Y.m.d H:i:s') . ' ' . $responseBody,
        FILE_APPEND
    );
    curl_close($curl);

    if (!empty($successPage) && is_file(__DIR__ . '/' . $successPage)) {
        include __DIR__ . '/' . $successPage;
    }
} catch (Exception $e) {
    @file_put_contents(
        __DIR__ . '/order.error.log',
        date('Y.m.d H:i:s') . ' ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString(),
        FILE_APPEND
    );

    if (!empty($errorPage) && is_file(__DIR__ . '/' . $errorPage)) {
        include __DIR__ . '/' . $errorPage;
    }
}