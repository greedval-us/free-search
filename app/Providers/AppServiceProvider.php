<?php

namespace App\Providers;

use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\TelegramAnalyticsApplicationService;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\TelegramParserApplicationService;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use App\Modules\Telegram\Search\TelegramSearchApplicationService;
use App\Modules\Telegram\TelegramService;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsApplicationService;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Parser\YouTubeParserApplicationService;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;
use App\Modules\YouTube\Search\YouTubeSearchApplicationService;
use App\Modules\YouTube\YouTubeDataApiClient;
use App\Modules\Shifr\Application\Contracts\ShifrClassicCipherServiceInterface;
use App\Modules\Shifr\Application\Contracts\ShifrToolkitServiceInterface;
use App\Modules\Shifr\Application\Services\ShifrClassicCipherService;
use App\Modules\Shifr\Application\Services\ShifrToolkitService;
use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Infrastructure\Providers\FioMultiSourceSearchProvider;
use App\Modules\EmailIntel\Application\Contracts\EmailDnsResolverInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailDomainWebSnapshotInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailTxtRecordLookupInterface;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailDnsResolver;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailDomainWebSnapshot;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailTxtRecordLookup;
use App\Modules\DocumentIntel\Application\Contracts\DocumentMetadataExtractorInterface;
use App\Modules\DocumentIntel\Application\Contracts\DocumentUrlCollectorInterface;
use App\Modules\DocumentIntel\Infrastructure\Clients\DocumentMetadataExtractor;
use App\Modules\DocumentIntel\Infrastructure\Clients\DocumentUrlCollector;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteWhoisClientInterface;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditHttpFetcherInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthHttpInspectorInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthSslInspectorInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Infrastructure\Providers\RssNewsFeedFetcher;
use App\Modules\SiteIntel\Infrastructure\Clients\DomainLiteDnsResolver;
use App\Modules\SiteIntel\Infrastructure\Clients\DomainLiteWhoisClient;
use App\Modules\SiteIntel\Infrastructure\Clients\SeoAuditHttpFetcher;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthDnsResolver;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthHttpInspector;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthSslInspector;
use App\Modules\DomainInfraIntel\Application\Contracts\AsnLookupClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\CertificateTransparencyClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainIpResolverInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainRdapClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\NeighborDomainResolverInterface;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\AsnLookupClient;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\CertificateTransparencyClient;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\DomainIpResolver;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\DomainRdapClient;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\NeighborDomainResolver;
use App\Modules\Username\Domain\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\Infrastructure\Http\UsernameSourceHttpChecker;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TelegramGatewayInterface::class, TelegramService::class);
        $this->app->bind(TelegramSearchApplicationServiceInterface::class, TelegramSearchApplicationService::class);
        $this->app->bind(TelegramParserApplicationServiceInterface::class, TelegramParserApplicationService::class);
        $this->app->bind(TelegramAnalyticsApplicationServiceInterface::class, TelegramAnalyticsApplicationService::class);
        $this->app->bind(YouTubeGatewayInterface::class, YouTubeDataApiClient::class);
        $this->app->bind(YouTubeSearchApplicationServiceInterface::class, YouTubeSearchApplicationService::class);
        $this->app->bind(YouTubeParserApplicationServiceInterface::class, YouTubeParserApplicationService::class);
        $this->app->bind(YouTubeAnalyticsApplicationServiceInterface::class, YouTubeAnalyticsApplicationService::class);
        $this->app->bind(ShifrClassicCipherServiceInterface::class, ShifrClassicCipherService::class);
        $this->app->bind(ShifrToolkitServiceInterface::class, ShifrToolkitService::class);
        $this->app->bind(UsernameSourceCheckerInterface::class, UsernameSourceHttpChecker::class);
        $this->app->bind(FioPublicSearchProviderInterface::class, FioMultiSourceSearchProvider::class);
        $this->app->bind(EmailDnsResolverInterface::class, EmailDnsResolver::class);
        $this->app->bind(EmailDomainWebSnapshotInterface::class, EmailDomainWebSnapshot::class);
        $this->app->bind(EmailTxtRecordLookupInterface::class, EmailTxtRecordLookup::class);
        $this->app->bind(DocumentUrlCollectorInterface::class, DocumentUrlCollector::class);
        $this->app->bind(DocumentMetadataExtractorInterface::class, DocumentMetadataExtractor::class);
        $this->app->bind(SiteHealthDnsResolverInterface::class, SiteHealthDnsResolver::class);
        $this->app->bind(SiteHealthHttpInspectorInterface::class, SiteHealthHttpInspector::class);
        $this->app->bind(SiteHealthSslInspectorInterface::class, SiteHealthSslInspector::class);
        $this->app->bind(SeoAuditHttpFetcherInterface::class, SeoAuditHttpFetcher::class);
        $this->app->bind(DomainLiteDnsResolverInterface::class, DomainLiteDnsResolver::class);
        $this->app->bind(DomainLiteWhoisClientInterface::class, DomainLiteWhoisClient::class);
        $this->app->bind(NewsFeedFetcherInterface::class, RssNewsFeedFetcher::class);
        $this->app->bind(DomainIpResolverInterface::class, DomainIpResolver::class);
        $this->app->bind(DomainRdapClientInterface::class, DomainRdapClient::class);
        $this->app->bind(CertificateTransparencyClientInterface::class, CertificateTransparencyClient::class);
        $this->app->bind(AsnLookupClientInterface::class, AsnLookupClient::class);
        $this->app->bind(NeighborDomainResolverInterface::class, NeighborDomainResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
