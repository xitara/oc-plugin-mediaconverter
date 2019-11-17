<?php namespace Xitara\MediaConverter\Classes;

class EroWrapper
{
    private $url = 'https://lady-anja.com/api/index.php';
    private $public = '8f709490330802fe659e533ace2c1505';
    private $private = '9e05db9355943c4f56ef24c57b14d268';

    public function __construct($action = false, $session = false, $data = false)
    {
        if ($action !== false) {
            return $this->$action($session, $data);
        }
    }

    public function login()
    {
        return $this->processLogin(false, ['user' => get('user'), 'pass' => get('pass')]);
    }

    public function logout()
    {
        return $this->processLogout();
    }

    public function userdata($userid)
    {
        return $this->getUserdata($userid);
    }

    public function userdataFromSession(string $session = null, string $combined = null)
    {
        if ($session === null) {
            if ($combined === null) {
                return false;
            }

            if (strpos($combined, '|') === false) {
                return false;
            }

            list($session, $userid) = explode('|', $combined);
        }

        $userdata = $this->getUserdataFromSession($session);

        if ($combined !== null) {
            if ($userdata['sessionID'] != $session) {
                return false;
            }

            if ($userdata['userID'] > 0 && $userdata['userID'] == $userid) {
                return $userdata;
            } else {
                return false;
            }
        }
        // return $userdata;
    }

    public function usersearch($string, $limit = 100)
    {
        return $this->getUsersearch($string, $limit);
    }

    public function balance($userid, $calc, $credits, $info = 'none')
    {
        return $this->processBalance($userid, $calc, $credits, $info);
    }

    private function processLogin($session, $data)
    {
        $this->postfields = [
            'api_name' => 'submit_login',
            'username' => $data['user'],
            'password' => $data['pass'],
        ];

        list($header, $data) = $this->accessAPI();

        // var_dump(get());
        // var_dump($data);

        // $this->getUserdata(false, $data['userID']);

        // exit;

        /**
         * test if login is successful
         */
        if (empty($data['error'])) {
            $success = $this->redirectTo(get('target'), $data);
        } else {
            $this->throwError($data['error']);
        }
    }

    private function processLogout()
    {
        $_SESSION = array();
        return true;
    }

    private function getUserdata($userid)
    {
        $this->postfields = [
            'api_name' => 'user_infos',
            'userid' => $userid,
        ];

        list($header, $data) = $this->accessAPI();

        $this->setSessiondata($data);
        return $data;
    }

    private function getUserdataFromSession($sessionid)
    {
        $this->postfields = [
            'api_name' => 'user_infos',
            'sessionid' => $sessionid,
        ];

        list($header, $data) = $this->accessAPI();

        $this->setSessiondata($data);
        return $data;
    }

    private function getUsersearch($string, $limit = 100)
    {
        $this->postfields = [
            'api_name' => 'user_search',
            'search_string' => $string,
            'limit' => $limit,
        ];

        list($header, $data) = $this->accessAPI();

        if (isset($data['error'])) {
            return false;
        }

        /**
         * workaround
         */
        $data = $data['user'];

        return $data;
    }

    private function processBalance($userid, $calc, $credits, $info)
    {
        $this->postfields = [
            'api_name' => 'edit_balance',
            'userid' => $userid,
            'calc' => $calc,
            'amount' => $credits,
            'infotext' => $info,
        ];

        list($header, $data) = $this->accessAPI();

        if ($data['status'] == 'ok') {
            $_SESSION['la_coins'] = $data['coins'];
        }

        return $data;
    }

    private function accessAPI()
    {
        $response_headers = [];
        $this->postfields['public_key'] = $this->public;
        $postfields = array_filter($this->postfields, 'strlen');
        ksort($postfields);
        $postfields = http_build_query($postfields);
        $hash = hash('sha512', $postfields . $this->private);
        $postfields .= '&hash=' . $hash;

        // var_dump($postfields);

        $request_headers = array(
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.98 Safari/537.36',
            'Content-Type: application/x-www-form-urlencoded',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$response_headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);

                if (count($header) < 2) {
                    return $len;
                }

                $name = strtolower(trim($header[0]));

                if (!array_key_exists($name, $response_headers)) {
                    $response_headers[$name] = [trim($header[1])];
                } else {
                    $response_headers[$name][] = trim($header[1]);
                }

                return $len;
            }
        );

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($ch);

        // var_dump($response_headers, $body);

        return array($response_headers, json_decode($body, true));
    }

    private function redirectTo($target, $data)
    {
        foreach ($data as $key => $item) {
            $target = str_replace(
                '[' . $key . ']',
                $item,
                $target
            );
        }

        $redirect = $data['loginURL'] . $target;

        // var_dump($data);
        // var_dump($this->callback);
        // var_dump($redirect);
        // exit;
        header('Location: ' . $redirect);
    }

    private function throwError($data)
    {
        $this->processLogout();
        echo $data;
    }

    public function setSessiondata($data)
    {
        if (!is_array($data) && !is_object($data)) {
            list($session, $id) = explode('|', $_SERVER['QUERY_STRING']);
            $_SESSION['la_sessionID'] = $session;
            $_SESSION['la_userID'] = $id;
            $_SESSION['la_logedin'] = 1;
        } else {
            foreach ($data as $key => $value) {
                if ($key == 'error') {
                    continue;
                }

                $_SESSION['la_' . $key] = $value;
            }
        }
    }
}
