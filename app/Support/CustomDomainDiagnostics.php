<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CustomDomainDiagnostics
{
    /**
     * @param  (callable(string): array{addresses: list<string>, cnames: list<string>})|null  $dnsResolver
     * @param  (callable(string, list<string>): bool)|null  $tlsVerifier
     * @return array{status: string, label: string, message: string, dns_ready: bool, tls_ready: bool, expected_target: string|null, checked_at: string|null}
     */
    public function diagnose(?string $domain, bool $refresh = false, ?callable $dnsResolver = null, ?callable $tlsVerifier = null): array
    {
        $normalizedDomain = strtolower(trim((string) $domain));
        $expectedTarget = $this->expectedTarget();

        if ($normalizedDomain === '') {
            return $this->result('not_configured', 'Não configurado', 'Informe e salve um domínio para iniciar a verificação.', false, false, $expectedTarget, null);
        }

        if (! config('inovaform.publication.custom_domain_diagnostics_enabled', true)) {
            return $this->result('checking_disabled', 'Verificação desativada', 'O domínio foi salvo, mas a verificação automática está desativada neste ambiente.', false, false, $expectedTarget, null);
        }

        if ($dnsResolver !== null || $tlsVerifier !== null) {
            return $this->runDiagnostics($normalizedDomain, $expectedTarget, $dnsResolver, $tlsVerifier);
        }

        $cacheKey = 'inovaform:custom-domain-diagnostics:'.sha1($normalizedDomain.'|'.$expectedTarget);

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            now()->addMinutes((int) config('inovaform.publication.custom_domain_diagnostics_cache_minutes', 5)),
            fn (): array => $this->runDiagnostics($normalizedDomain, $expectedTarget),
        );
    }

    /**
     * @param  (callable(string): array{addresses: list<string>, cnames: list<string>})|null  $dnsResolver
     * @param  (callable(string, list<string>): bool)|null  $tlsVerifier
     * @return array{status: string, label: string, message: string, dns_ready: bool, tls_ready: bool, expected_target: string|null, checked_at: string|null}
     */
    private function runDiagnostics(string $domain, ?string $expectedTarget, ?callable $dnsResolver = null, ?callable $tlsVerifier = null): array
    {
        $records = $dnsResolver !== null ? $dnsResolver($domain) : $this->resolveDns($domain);
        $addresses = array_values(array_unique(array_filter($records['addresses'])));
        $cnames = array_map(static fn (string $value): string => strtolower(rtrim($value, '.')), $records['cnames']);
        $expectedAddresses = $this->expectedAddresses($expectedTarget, $dnsResolver);

        $dnsReady = $expectedTarget === null
            ? ($addresses !== [] || $cnames !== [])
            : in_array($expectedTarget, $cnames, true) || array_intersect($addresses, $expectedAddresses) !== [];

        if (! $dnsReady) {
            $instruction = $expectedTarget !== null
                ? "Aponte um CNAME para {$expectedTarget} ou use os mesmos registros A/AAAA."
                : 'O domínio ainda não possui registros DNS públicos detectáveis.';

            return $this->result('pending_dns', 'DNS pendente', $instruction, false, false, $expectedTarget, now()->toIso8601String());
        }

        $tlsReady = $tlsVerifier !== null ? $tlsVerifier($domain, $addresses) : $this->hasValidTls($domain, $addresses);

        if (! $tlsReady) {
            return $this->result(
                'tls_pending',
                'TLS pendente',
                'O DNS está correto, mas ainda não foi possível validar um certificado HTTPS para o domínio.',
                true,
                false,
                $expectedTarget,
                now()->toIso8601String(),
            );
        }

        return $this->result(
            'ready',
            'Domínio pronto',
            'DNS e certificado HTTPS foram validados. O domínio está pronto para receber acessos.',
            true,
            true,
            $expectedTarget,
            now()->toIso8601String(),
        );
    }

    /**
     * @return array{addresses: list<string>, cnames: list<string>}
     */
    private function resolveDns(string $domain): array
    {
        $records = @dns_get_record($domain, DNS_A | DNS_AAAA | DNS_CNAME);
        $addresses = [];
        $cnames = [];

        foreach (is_array($records) ? $records : [] as $record) {
            if (isset($record['ip'])) {
                $addresses[] = (string) $record['ip'];
            }

            if (isset($record['ipv6'])) {
                $addresses[] = (string) $record['ipv6'];
            }

            if (isset($record['target'])) {
                $cnames[] = (string) $record['target'];
            }
        }

        return [
            'addresses' => array_values(array_unique($addresses)),
            'cnames' => array_values(array_unique($cnames)),
        ];
    }

    /**
     * @return list<string>
     */
    private function expectedAddresses(?string $expectedTarget, ?callable $dnsResolver = null): array
    {
        $configuredAddresses = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            (array) config('inovaform.publication.custom_domain_target_ips', []),
        )));

        if ($expectedTarget === null) {
            return $configuredAddresses;
        }

        $resolvedTarget = $dnsResolver !== null ? $dnsResolver($expectedTarget) : $this->resolveDns($expectedTarget);

        return array_values(array_unique([...$configuredAddresses, ...$resolvedTarget['addresses']]));
    }

    /**
     * @param  list<string>  $addresses
     */
    private function hasValidTls(string $domain, array $addresses): bool
    {
        $publicAddresses = array_values(array_filter($addresses, static function (string $address): bool {
            return filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) !== false;
        }));

        if ($publicAddresses === []) {
            return false;
        }

        $context = stream_context_create([
            'ssl' => [
                'peer_name' => $domain,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'SNI_enabled' => true,
            ],
        ]);
        $timeoutSeconds = max(1, (int) config('inovaform.publication.custom_domain_tls_timeout_seconds', 3));

        foreach ($publicAddresses as $address) {
            $socketAddress = str_contains($address, ':') ? "[{$address}]" : $address;
            $socket = @stream_socket_client(
                "tls://{$socketAddress}:443",
                $errorCode,
                $errorMessage,
                $timeoutSeconds,
                STREAM_CLIENT_CONNECT,
                $context,
            );

            if (is_resource($socket)) {
                fclose($socket);

                return true;
            }
        }

        return false;
    }

    private function expectedTarget(): ?string
    {
        $target = strtolower(rtrim(trim((string) config('inovaform.publication.custom_domain_target_host')), '.'));

        return $target !== '' ? $target : null;
    }

    /**
     * @return array{status: string, label: string, message: string, dns_ready: bool, tls_ready: bool, expected_target: string|null, checked_at: string|null}
     */
    private function result(
        string $status,
        string $label,
        string $message,
        bool $dnsReady,
        bool $tlsReady,
        ?string $expectedTarget,
        ?string $checkedAt,
    ): array {
        return [
            'status' => $status,
            'label' => $label,
            'message' => $message,
            'dns_ready' => $dnsReady,
            'tls_ready' => $tlsReady,
            'expected_target' => $expectedTarget,
            'checked_at' => $checkedAt,
        ];
    }
}
