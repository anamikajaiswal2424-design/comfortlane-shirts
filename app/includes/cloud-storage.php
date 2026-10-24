<?php
declare(strict_types=1);

function cloudKey(string $name): string
{
    $prefix = (string) (getenv('STORE_REDIS_PREFIX') ?: 'comfortlane:production:');
    return $prefix . $name;
}

function cloudCommand(array $command): mixed
{
    $url = rtrim((string) (getenv('UPSTASH_REDIS_REST_URL') ?: getenv('KV_REST_API_URL') ?: ''), '/');
    $token = (string) (getenv('UPSTASH_REDIS_REST_TOKEN') ?: getenv('KV_REST_API_TOKEN') ?: '');
    $scheme = parse_url($url, PHP_URL_SCHEME);
    $host = parse_url($url, PHP_URL_HOST);
    $local = !getenv('VERCEL') && $scheme === 'http' && in_array($host, ['127.0.0.1', 'localhost'], true);
    if (($scheme !== 'https' && !$local) || $token === '') throw new RuntimeException('Persistent Redis storage is not configured.');
    $payload = json_encode($command, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $context = stream_context_create([
        'http' => ['method' => 'POST', 'header' => "Authorization: Bearer " . $token . "\r\nContent-Type: application/json\r\nConnection: close\r\n",
            'content' => $payload, 'timeout' => 6, 'ignore_errors' => true, 'follow_location' => 0],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents($url, false, $context);
    $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);
    $status = $headers[0] ?? '';
    if (!is_string($body) || !preg_match('#^HTTP/\S+ 200(?: |$)#', $status)) throw new RuntimeException('Persistent storage request failed.');
    $result = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($result) || isset($result['error']) || !array_key_exists('result', $result)) throw new RuntimeException('Persistent storage rejected the command.');
    return $result['result'];
}

function cloudReadText(string $path): string|false
{
    $name = basename($path);
    if (!isset($GLOBALS['__storeFileCache'])) {
        $names = ['products.json', 'payment.json', 'tracking.json', 'business.json'];
        $values = cloudCommand(array_merge(['MGET'], array_map(static fn($n) => cloudKey('file:' . $n), $names)));
        $GLOBALS['__storeFileCache'] = array_combine($names, $values);
    }
    $stored = $GLOBALS['__storeFileCache'][$name] ?? null;
    if (is_string($stored)) return $stored;
    return is_file($path) ? file_get_contents($path) : false;
}

function cloudWriteText(string $path, string $value): int|false
{
    $ok = cloudCommand(['SET', cloudKey('file:' . basename($path)), $value]) === 'OK';
    if ($ok) $GLOBALS['__storeFileCache'][basename($path)] = $value;
    return $ok ? strlen($value) : false;
}

function cloudAppendEvent(array $event): bool
{
    $encoded = json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $key = cloudKey('analytics');
    // Keep the bounded event list and append operation atomic.
    $script = "redis.call('RPUSH',KEYS[1],ARGV[1]); redis.call('LTRIM',KEYS[1],-20000,-1); return 1";
    return (int) cloudCommand(['EVAL', $script, 1, $key, $encoded]) === 1;
}

function cloudAppendOrder(array $order): bool
{
    $encoded = json_encode($order, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // Receipts are stored by immutable order ID. Duplicate retries are idempotent.
    $script = <<<'LUA'
local inserted=redis.call('HSETNX',KEYS[1],ARGV[1],ARGV[2])
if inserted==1 then
  redis.call('ZADD',KEYS[2],ARGV[3],ARGV[1])
  local count=redis.call('ZCARD',KEYS[2])
  if count>2500 then
    local old=redis.call('ZRANGE',KEYS[2],0,count-2501)
    for _,id in ipairs(old) do redis.call('HDEL',KEYS[1],id);redis.call('ZREM',KEYS[2],id) end
  end
end
return 1
LUA;
    return (int) cloudCommand(['EVAL', $script, 2, cloudKey('orders'), cloudKey('order-index'),
        (string) $order['id'], $encoded, (string) microtime(true)]) === 1;
}

final class CloudSessions implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private string $lockKey = '';
    private string $lockToken = '';
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool
    {
        if ($this->lockKey !== '') {
            $script = "if redis.call('GET',KEYS[1])==ARGV[1] then return redis.call('DEL',KEYS[1]) else return 0 end";
            cloudCommand(['EVAL', $script, 1, $this->lockKey, $this->lockToken]);
            $this->lockKey = ''; $this->lockToken = '';
        }
        return true;
    }
    private function lock(string $id): void
    {
        $key = cloudKey('session-lock:' . $id);
        if ($this->lockKey === $key) return;
        if ($this->lockKey !== '') $this->close();
        $token = bin2hex(random_bytes(16));
        $deadline = microtime(true) + 3;
        do {
            if (cloudCommand(['SET', $key, $token, 'NX', 'PX', 65000]) === 'OK') {
                $this->lockKey = $key; $this->lockToken = $token; return;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);
        throw new RuntimeException('Checkout session busy; retry the request.');
    }
    public function read(string $id): string|false
    {
        $this->lock($id);
        $value = cloudCommand(['GET', cloudKey('session:' . $id)]);
        return is_string($value) ? (base64_decode($value, true) ?: '') : '';
    }
    public function write(string $id, string $data): bool
    {
        $this->lock($id);
        // A stale function must not overwrite a session after losing its lease.
        $script = "if redis.call('GET',KEYS[1])~=ARGV[1] then return 0 end; redis.call('SET',KEYS[2],ARGV[2],'EX',86400);return 1";
        return (int) cloudCommand(['EVAL', $script, 2, $this->lockKey, cloudKey('session:' . $id),
            $this->lockToken, base64_encode($data)]) === 1;
    }
    public function destroy(string $id): bool { cloudCommand(['DEL', cloudKey('session:' . $id)]); return true; }
    public function gc(int $max_lifetime): int|false { return 0; }
    public function validateId(string $id): bool { return (bool) cloudCommand(['EXISTS', cloudKey('session:' . $id)]); }
    public function updateTimestamp(string $id, string $data): bool { return $this->write($id, $data); }
}

function cloudBootstrap(): void
{
    static $ready = false;
    if ($ready) return;
    // Fail before accepting checkout if persistent storage is missing.
    if (!getenv('UPSTASH_REDIS_REST_URL') && !getenv('KV_REST_API_URL')) throw new RuntimeException('Connect Upstash Redis in Vercel first.');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', getenv('VERCEL') || ($_SERVER['HTTPS'] ?? '') === 'on' ? '1' : '0');
    ini_set('session.gc_maxlifetime', '86400');
    session_set_save_handler(new CloudSessions(), true);
    $ready = true;
}
