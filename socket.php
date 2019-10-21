<?php
    require_once './config.php';
    require __DIR__ . '/vendor/autoload.php';
    $address = "127.0.0.1";
    $port = "9999";
    $tcp = getprotobyname('tcp');
    $sock = socket_create(AF_INET, SOCK_STREAM, $tcp);
    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
    if ($sock < 0) {
        throw new Exception('failed to create socket: '.socket_strerror($sock)."\n");
    }
    socket_bind($sock, $address, $port);
    socket_listen($sock, $port);
    echo "listen on $address $port ... \n";
    $sockets = $sock;
    $clients[] = $sockets;

    while (true) {
        $changes = $clients;
        $write = null;
        $except = null;
        $sckt = socket_select($changes, $write, $except, null);
        if ($sckt < 1) {
            echo 'socket_select() failed, reason: '.socket_strerror(socket_last_error())."\n";
            continue;
        }
        foreach ($changes as $key => $_sock) {
            if ($sockets == $_sock) { //判断是不是新接入的socket
                if (false === ($newClient = socket_accept($_sock))) {
                    die('failed to accept socket: '.socket_strerror($_sock)."\n");
                }
                $line = trim(socket_read($newClient, 1024));
                handshaking($newClient, $line, $address, $port);
                //获取client ip
                socket_getpeername($newClient, $ip);
                array_push($clients, $newClient);
            } else {
                socket_recv($_sock, $buffer, 2048, 0);
                $messageJson = json_decode(message($buffer), true);
                $uuid = $messageJson['uuid'];
                $client = new MongoDB\Client($dbConnectionStr);
                $databaseManager = $client->admama;
                $qrUUid = $databaseManager->qr_uuid;
                $issetUUid = $qrUUid->findOne(['uuid' => $uuid, 'isLogin' => 1]);
                if(!empty($issetUUid)){
                    $response['code'] = 200;
                }else{
                    $response['code'] = 500;
                }
                $responseJson = json_encode($response);
                send($_sock, $responseJson);
            }
        }
    }



    function handshaking($newClient, $line, $address, $port)
    {
        $headers = array();
        $lines = preg_split("/\r\n/", $line);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }
        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n".
                "Upgrade: websocket\r\n".
                "Connection: Upgrade\r\n".
                "WebSocket-Origin: $address\r\n".
                "WebSocket-Location: ws://$address:$port/websocket/websocket\r\n".
                "Sec-WebSocket-Accept:$secAccept\r\n\r\n";

        return socket_write($newClient, $upgrade, strlen($upgrade));
    }

    /**
     * 解析接收数据.
     *
     * @param $buffer
     *
     * @return null|string
     */
    function message($buffer)
    {
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if (126 === $len) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } elseif (127 === $len) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); ++$index) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return $decoded;
    }

    /**
     * 发送数据.
     *
     * @param $newClinet 新接入的socket
     * @param $msg   要发送的数据
     *
     * @return int|string
     */
    function send($newClinet, $msg)
    {
        $msg = frame($msg);
        socket_write($newClinet, $msg, strlen($msg));
    }

    function frame($s)
    {
        $a = str_split($s, 125);
        if (1 == count($a)) {
            return "\x81".chr(strlen($a[0])).$a[0];
        }
        $ns = '';
        foreach ($a as $o) {
            $ns .= "\x81".chr(strlen($o)).$o;
        }

        return $ns;
    }
?>