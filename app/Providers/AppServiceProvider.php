<?php

namespace App\Providers;

use App\Services\Casaku\CasakuGateway;
use App\Services\Casaku\FakeCasakuGateway;
use App\Services\Casaku\HttpCasakuGateway;
use App\Services\CredentialVault;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CredentialVault::class, fn () => new CredentialVault(config('enovaz.credential_key')));

        $this->app->singleton(CasakuGateway::class, function ($app) {
            $cfg = config('services.casaku');

            if (($cfg['driver'] ?? 'fake') === 'live') {
                return new HttpCasakuGateway(
                    (string) $cfg['base_url'],
                    (string) $cfg['license_key'],
                    (string) $cfg['qr_id'],
                    (array) $cfg['package_ids'],
                    (string) $cfg['prefix'],
                );
            }

            if ($app->isProduction()) {
                throw new RuntimeException('CASAKU_DRIVER=fake tidak boleh dipakai di production.');
            }

            return new FakeCasakuGateway;
        });
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        // Default = aturan pembeli. Admin memakai aturan lebih panjang (User::passwordRule()).
        Password::defaults(fn () => Password::min((int) config('enovaz.password_min.pembeli', 12))->letters()->numbers());

        // Paksa HTTPS hanya bila memang dilayani lewat HTTPS (APP_URL https://).
        // Di LAN tanpa sertifikat, memaksa https akan merusak semua link, CSS, dan form.
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
