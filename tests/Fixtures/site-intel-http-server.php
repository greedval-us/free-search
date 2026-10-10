<?php

// One local connection, gzip content, no application boot or external requests.
$server = stream_socket_server('tcp://127.0.0.1:0', $errorNumber, $errorMessage);
if ($server === false) {
    throw new RuntimeException($errorMessage, $errorNumber);
}

try {
    fwrite(STDOUT, 'LISTEN '.stream_socket_get_name($server, false)."\n");
    fflush(STDOUT);
    $client = stream_socket_accept($server, 5);
    if ($client === false) {
        throw new RuntimeException('Local test client did not connect.');
    }
    try {
        stream_set_timeout($client, 3);
        while (($line = fgets($client)) !== false && trim($line) !== '') {
        }
        $body = gzencode(str_repeat('x', (int) $argv[1]));
        fwrite($client, "HTTP/1.1 200 OK\r\nContent-Encoding: gzip\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n".$body);
    } finally {
        fclose($client);
    }
} finally {
    fclose($server);
}
