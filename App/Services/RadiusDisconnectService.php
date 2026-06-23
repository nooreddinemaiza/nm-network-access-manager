<?php

namespace App\Services;

class RadiusDisconnectService
{
    protected string $nasIp;
    protected string $secret;

    public function __construct()
    {
        $this->nasIp = '192.168.0.200';
        $this->secret = 'radius123';
        /*
        echo "User-Name=test" \
        | radclient -x 192.168.0.200:3799 disconnect radius123
        */
    }

    public function disconnect(
        string $username,
        string $sessionId
    ): bool {
        $payload = sprintf(
            "User-Name=%s,Acct-Session-Id=%s",
            $username,
            $sessionId
        );

        $command = sprintf(
            'echo %s | radclient -x %s disconnect %s',
            escapeshellarg($payload),
            escapeshellarg($this->nasIp),
            escapeshellarg($this->secret)
        );

        exec($command, $output, $status);

        return $status === 0;
    }

    // ── Approche 2 : Disconnect-Request RADIUS (CoA) ────────────────────────
    // Décommentez et adaptez si votre NAS supporte RFC 3576.
    //
    // $nasIp     = $this->nasIp;
    // $nasSecret = $this->secret;
    // $cmd = sprintf(
    //     'echo "User-Name=%s,Acct-Session-Id=%s" | radclient -x %s:3799 disconnect %s 2>&1',
    //     escapeshellarg($username),
    //     escapeshellarg($sessionId),
    //     escapeshellarg($nasIp),
    //     escapeshellarg($nasSecret)
    // );
    // $output = shell_exec($cmd);
    // return str_contains($output ?? '', 'Disconnect-ACK');

}
