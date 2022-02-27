<?php

namespace App;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;
use function in_array;

class Client
{
    private array $options;

    private ?string $token = null;

    public function __construct()
    {
        $this->options = [
            'base_uri' => config('proxy.host'),
            'verify'   => false,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Host>
     */
    public function getHosts()
    {
        return Http::withOptions($this->options)
            ->withToken($this->getToken())
            ->get('/api/nginx/proxy-hosts', [
                'expand' => 'owner,access_list,certificate',
            ])->collect()->mapInto(Host::class);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Certificate>
     */
    public function getCertificates()
    {
        return Http::withOptions($this->options)
            ->withToken($this->getToken())
            ->get('/api/nginx/certificates')
            ->collect()->mapInto(Certificate::class);
    }

    private function createCertificate(string $name, array $domains): Certificate
    {
        $response = Http::withOptions($this->options)
            ->withToken($this->getToken())
            ->post('/api/nginx/certificates', [
                'nice_name' => $name,
                'provider'  => 'other',
            ]);
        $certificate = new Certificate($response->json());
        $key_path = tempnam(sys_get_temp_dir(), 'cert-key-');
        $cert_path = tempnam(sys_get_temp_dir(), 'cert-');
        $process = new Process([
            'mkcert',
            '-cert-file',
            $cert_path,
            '-key-file',
            $key_path,
            ...$domains,
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException("Unable to create certificate files: " . $process->getErrorOutput());
        }
        $key = file_get_contents($key_path);
        $cert = file_get_contents($cert_path);
        Http::withOptions($this->options)
            ->withToken($this->getToken())
            ->attach('certificate', $cert, 'certificate.crt')
            ->attach('certificate_key', $key, 'certificate.key')
            ->post("/api/nginx/certificates/$certificate->id/upload");

        return $certificate;
    }

    public function domainExists(string $domain)
    {
        return $this->getHosts()->first(fn(Host $host) => in_array($domain, $host->domains, true)) !== null;
    }

    /**
     * @param array $domains
     * @param string $host
     * @param int $port
     * @param bool $https
     * @return \App\Host
     * @throws \Exception
     */
    public function createHost(array $domains, string $host, int $port, bool $https): Host
    {
        $existing_domains = $this->getHosts()->flatMap(fn(Host $host) => $host->domains)->toArray();
        foreach ($domains as $domain) {
            if (in_array($domain, $existing_domains, true)) {
                throw new RuntimeException("A host with the domain $domain already exists");
            }
        }
        $payload = [
            'domain_names'            => $domains,
            'forward_scheme'          => 'http',
            'forward_host'            => $host,
            'forward_port'            => $port,
            'allow_websocket_upgrade' => true,
            'access_list_id'          => '0',
            'certificate_id'          => 0,
            'ssl_forced'              => false,
            'http2_support'           => false,
            'meta'                    => [
                'letsencrypt_agree' => false,
                'dns_challenge'     => false,
            ],
            'advanced_config'         => '',
            'locations'               => [],
            'block_exploits'          => false,
            'caching_enabled'         => false,
            'hsts_enabled'            => false,
            'hsts_subdomains'         => false,
        ];
        if ($https) {
            $cert_name = collect($domains)->join('--');
            $certificate = $this->createCertificate($cert_name, $domains);
            $payload['certificate_id'] = $certificate->id;
            $payload['ssl_forced'] = true;
            $payload['http2_support'] = true;
        }
        $data = Http::withOptions($this->options)
            ->withToken($this->getToken())
            ->post('/api/nginx/proxy-hosts', $payload)
            ->json();

        return new Host($data);
    }

    private function getToken(): string
    {
        if (!$this->token) {
            $host = config('proxy.host');
            $email = config('proxy.email');
            $password = config('proxy.password');
            if (!$host || !$email || !$password) {
                throw new RuntimeException('Proxy manager environment variables missing');
            }
            $response = Http::withOptions($this->options)
                ->post('/api/tokens', [
                    'identity' => $email,
                    'secret'   => $password,
                ]);
            $this->token = $response->json('token');
        }

        return $this->token;
    }

}
