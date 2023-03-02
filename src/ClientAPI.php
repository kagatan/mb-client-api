<?php


namespace Kagatan\MikBillClientAPI;


use GuzzleHttp\Client;
use function Monolog\pushHandler;


if (!function_exists('storage_path')) {

    function storage_path($path)
    {
        return './' . $path;
    }
}

class ClientAPI
{
    private $secret_key = null;
    private $jwt_token = null;
    private $client = null;

    private $logger = null;
    private $log_path = 'logs/api-client.log';


    public function __construct($host, $secret_key = null, $debug = false)
    {
        $this->secret_key = $secret_key;

        $config = [
            'base_uri' => $host,
            'verify'   => false
        ];

        if ($debug) {
            $config['handler'] = $this->createLoggingHandlerStack([
                'METHOD: {method} {uri} HTTP/{version}',
                'REQUEST: {req_body}',
                'RESPONSE: {code} - {res_body}',
                '******************************'
            ]);
        }
        $this->client = new Client($config);
    }

    public function setLogPath($path)
    {
        $this->log_path = $path;
    }

    private function createLoggingHandlerStack(array $templates)
    {
        $stack = \GuzzleHttp\HandlerStack::create();

        foreach ($templates as $template) {
            $stack->unshift(
                $this->createGuzzleLoggingMiddleware($template)
            );
        }

        return $stack;
    }

    private function getLogger()
    {
        if (!$this->logger) {

            $this->logger = new \Monolog\Logger('api-сlient');

            $this->logger->pushHandler(
                new \Monolog\Handler\RotatingFileHandler(storage_path($this->log_path))
            );
        }

        return $this->logger;
    }


    private function createGuzzleLoggingMiddleware($messageFormat)
    {
        return \GuzzleHttp\Middleware::log(
            $this->getLogger(),
            new \GuzzleHttp\MessageFormatter($messageFormat)
        );
    }


    public function setJWT($token)
    {
        $this->jwt_token = $token;
    }


    /**
     * Поиск в биллинге абонента по user_id telegram`a
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param $value
     * @param string $key
     * @return bool|mixed
     */
    public function searchUser($value, $key = 'user_id')
    {
        $params = [
            'field' => $key,
            'value' => $value
        ];

        return $this->sendRequest('/api/v1/billing/users/search', 'POST', $params, true);
    }


    /**
     * Привязка абонента к user_id telegram`a
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param $user_id
     * @param $uid
     * @return bool|mixed
     */
    public function bindUser($user_id, $uid)
    {
        $params = [
            'user_id' => $user_id,
            'uid'     => $uid,
        ];

        return $this->sendRequest('/api/v1/billing/users/bind', 'POST', $params, true);
    }


    /**
     * Получим JWT токен для работы пользовательским API
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param $uid
     * @return bool|mixed
     */
    public function getUserToken($uid)
    {
        $params = [
            "uid" => $uid
        ];
        $response = $this->sendRequest('/api/v1/billing/users/token', 'POST', $params, true);

        // Если пришел токен пропишем его
        if (isset($response['data']['token'])) {
            $this->setJWT($response['data']['token']);
        }

        return $response;
    }


    /**
     * Получить информацию об абоненте
     *
     * @return bool|mixed
     */
    public function getUser()
    {
        return $this->sendRequest('/api/v1/cabinet/user', 'GET');
    }


    /**
     * Авторизация пользователя
     *
     * @param $login
     * @param $pass
     * @return mixed
     */
    public function auth($login, $pass)
    {
        $params = [
            'login'    => $login,
            'password' => $pass,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/login', 'POST', $params);
    }


    /**
     * Авторизация по телефону
     *
     * @param $phone
     * @return bool|mixed
     */
    public function authPhone($phone)
    {
        $params = [
            'phone' => $phone,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/phone', 'POST', $params);
    }

    /**
     * Ввод кода отп для авторизации по телефону
     *
     * @param $otp
     * @return bool|mixed
     */
    public function authPhoneOtpApply($otp)
    {
        $params = [
            'otp' => $otp,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/phone/otp', 'POST', $params);
    }


    private function sendRequest($uri, $method = 'POST', $params = [], $sign = false)
    {
        $headers = [];

        if ($sign) {
            $salt = uniqid();
            $params['salt'] = $salt;
            $params['sign'] = hash_hmac('sha512', $salt, $this->secret_key);
        } else {
            $headers['Authorization'] = $this->jwt_token;
        }

        $res = $this->client->request($method, $uri, [
            'form_params' => $params,
            'headers'     => $headers
        ]);

        if ($res->getStatusCode() == 200) { // 200 OK
            return json_decode($res->getBody()->getContents(), true);
        }

        return false;
    }

}