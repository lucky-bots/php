<?php
require __DIR__ . '/../vendor/autoload.php';
 
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
 
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;
 
$pass_signature = true;
 
// set LINE channel_access_token and channel_secret
$channel_access_token = "EUreS4urZHYaKl+r0Hc9WVJaG03NvyeCPx6iAn/kQyiXz6nxpvipc3T77DV7mMzrQ7SABUzH/J6G7ReeCFlgM0xQG388iOrY4e5WKZ6m2rNoCZiNl8PV5DHSyfT3hif9J7utesf0T2Am6Kuss6tQEwdB04t89/1O/w1cDnyilFU=";
$channel_secret = "15f798f915c02f0859c39798570a61bf";
 
// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
 
$app = AppFactory::create();
$app->setBasePath("/public");
 
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello World!");
    return $response;
});
 
// buat route untuk webhook
$app->post('/webhook', function (Request $request, Response $response) use ($channel_secret, $bot, $httpClient, $pass_signature) {
    // get request body and line signature header
    $body = $request->getBody();
    $signature = $request->getHeaderLine('HTTP_X_LINE_SIGNATURE');
 
    // log body and signature
    file_put_contents('php://stderr', 'Body: ' . $body);
 
    if ($pass_signature === false) {
        // is LINE_SIGNATURE exists in request header?
        if (empty($signature)) {
            return $response->withStatus(400, 'Signature not set');
        }
 
        // is this request comes from LINE?
        if (!SignatureValidator::validateSignature($body, $channel_secret, $signature)) {
            return $response->withStatus(400, 'Invalid signature');
        }
    }
 
    
// kode aplikasi 

$data = json_decode($body, true);
    if (is_array($data['events'])) {
        foreach ($data['events'] as $event) {
            if ($event['type'] == 'message') {
                // message from group / room
                if ($event['source']['type'] == 'group' or
                    $event['source']['type'] == 'room'
                ) {
 
                    
                // message from single user
                } else {
                    if ($event['message']['type'] == 'text') {
                    if (strtolower($event['message']['text']) == 'user id') {
 
                        $result = $bot->replyText($event['replyToken'], $event['source']['userId']);
 
                    } elseif (strtolower($event['message']['text']) == 'flex message') {
 
                        $flexTemplate = file_get_contents("../flex_message.json"); // template flex message
                        $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                            'replyToken' => $event['replyToken'],
                            'messages'   => [
                                [
                                    'type'     => 'flex',
                                    'altText'  => 'Test Flex Message',
                                    'contents' => json_decode($flexTemplate)
                                ]
                            ],
                        ]);
 
                    } else {

                        $message = 'oppsss salah silakan ketik flex message untuk mencoba fitur bot ini.';
                        $textMessageBuilder = new TextMessageBuilder($message);
                        $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                        
                    }
 
                    $response->getBody()->write($result->getJSONDecodedBody());
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($result->getHTTPStatus());
                }
                    
 
                }
            }
        }
    }
 
    return $response->withStatus(400, 'No event sent!');
});

$app->get('/pushmessage', function ($req, $response) use ($bot) {
    // send push message to user
    $userId = 'U9e2374fc931558863e29610f7c040064';
    $textMessageBuilder = new TextMessageBuilder('Halo, ini pesan push');
    $result = $bot->pushMessage($userId, $textMessageBuilder);
 
    $response->getBody()->write("Pesan push berhasil dikirim!");
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});


$app->run();