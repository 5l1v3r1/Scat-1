<?php

namespace Cvar1984\Scat;

final class Server implements ScatInterface
{
    private string $method = 'aes-128-ctr';
    private string $key;
    private string $iv;

    public function __construct(string $host, int $port)
    {
        $this->key = openssl_digest(php_uname(), 'SHA256', true);
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($this->socket, $host, $port);
        socket_listen($this->socket);
        $this->connection = socket_accept($this->socket);
    }
    protected function encryptMessage(string $token): string
    {
        $ivLength = openssl_cipher_iv_length($this->method);
        $this->iv = openssl_random_pseudo_bytes($ivLength);
        $result = openssl_encrypt($token, $this->method, $key, 0, $this->iv);
        return $result . '::' . bin2hex($this->iv);
    }
    protected function decryptMessage(string $token): string
    {
        list($token, $this->iv) = explode('::', $token);
        return openssl_decrypt(
            $token,
            $this->method,
            $this->key,
            0,
            hex2bin($this->iv)
        );
    }
    public function getMessage(): string
    {
        $encryptedToken = socket_read($this->connection, 1024);
        return $this->decryptMessage($encryptedToken);
    }
    public function sendMessage(string $message): bool
    {
        $encrypted = $this->encryptMessage($message);
        return socket_write($this->connection, $encrypted, strlen($encrypted));
    }
    public function __destruct()
    {
        socket_close($this->connection);
        socket_close($this->socket);
    }
}
