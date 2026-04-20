import { computed } from 'vue';
import type { Ref } from 'vue';
import type { SiteIntelAnalyticsResult } from '../types';

type TranslateFn = (key: string) => string;

export type Tone = 'sky' | 'emerald' | 'amber' | 'rose' | 'slate';

export type ChartBar = {
    label: string;
    value: number;
    percent: number;
    tone: Tone;
};

export const useSiteIntelAnalyticsViewModel = (
    result: Ref<SiteIntelAnalyticsResult | null>,
    t: TranslateFn,
) => {
    const scoreBadgeClass = computed(() => {
        const level = result.value?.overview.score.level;

        if (level === 'high') {
            return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300';
        }

        if (level === 'medium') {
            return 'border-amber-500/40 bg-amber-500/10 text-amber-300';
        }

        return 'border-rose-500/40 bg-rose-500/10 text-rose-300';
    });

    const formatDateTime = (value: string | null) => {
        if (!value) {
            return '-';
        }

        return new Date(value).toLocaleString();
    };

    const recommendationLabel = (key: string) => {
        const translationKey = `siteIntel.analytics.recommendation.${key}`;
        const translated = t(translationKey);

        return translated === translationKey ? key : translated;
    };

    const signalLabel = (key: string, type: 'risk' | 'strength') => {
        const translationKey = `siteIntel.analytics.signal.${type}.${key}`;
        const translated = t(translationKey);

        return translated === translationKey ? key : translated;
    };

    const clampPercent = (value: number) => {
        if (!Number.isFinite(value)) {
            return 0;
        }

        return Math.max(0, Math.min(100, Math.round(value)));
    };

    const scoreBars = computed<ChartBar[]>(() => {
        if (!result.value) {
            return [];
        }

        const overall = result.value.overview.score.value;
        const health = result.value.overview.healthScore;
        const domainSafety = Math.max(0, 100 - result.value.overview.domainRiskScore);
        const headers = result.value.overview.headersCoverage.percent;

        return [
            {
                label: t('siteIntel.analytics.overallScore'),
                value: overall,
                percent: clampPercent(overall),
                tone: 'sky',
            },
            {
                label: t('siteIntel.analytics.healthScore'),
                value: health,
                percent: clampPercent(health),
                tone: 'emerald',
            },
            {
                label: t('siteIntel.analytics.domainSafetyScore'),
                value: domainSafety,
                percent: clampPercent(domainSafety),
                tone: 'amber',
            },
            {
                label: t('siteIntel.analytics.headersCoverage'),
                value: headers,
                percent: clampPercent(headers),
                tone: 'slate',
            },
        ];
    });

    const dnsBars = computed<ChartBar[]>(() => {
        if (!result.value) {
            return [];
        }

        const dns = result.value.domainLite.dns;
        const points = [
            { label: 'A', value: dns.a.length },
            { label: 'AAAA', value: dns.aaaa.length },
            { label: 'NS', value: dns.ns.length },
            { label: 'MX', value: dns.mx.length },
            { label: 'TXT', value: dns.txt.length },
            { label: 'CAA', value: dns.caa.length },
        ];
        const max = Math.max(1, ...points.map((item) => item.value));

        return points.map((item) => ({
            label: item.label,
            value: item.value,
            percent: clampPercent((item.value / max) * 100),
            tone: 'sky',
        }));
    });

    const httpLatencyBars = computed<ChartBar[]>(() => {
        if (!result.value) {
            return [];
        }

        const chain = result.value.siteHealth.http.chain;
        const maxLatency = Math.max(1, ...chain.map((step) => step.responseTimeMs));

        return chain.map((step, index) => ({
            label: `#${index + 1} - ${step.status || '-'}`,
            value: step.responseTimeMs,
            percent: clampPercent((step.responseTimeMs / maxLatency) * 100),
            tone: step.error ? 'rose' : 'emerald',
        }));
    });

    const signalBars = computed<ChartBar[]>(() => {
        if (!result.value) {
            return [];
        }

        const risks = result.value.overview.signals.risks.length;
        const strengths = result.value.overview.signals.strengths.length;
        const max = Math.max(1, risks, strengths);

        return [
            {
                label: t('siteIntel.analytics.riskSignals'),
                value: risks,
                percent: clampPercent((risks / max) * 100),
                tone: 'rose',
            },
            {
                label: t('siteIntel.analytics.strengthSignals'),
                value: strengths,
                percent: clampPercent((strengths / max) * 100),
                tone: 'emerald',
            },
        ];
    });

    const recommendationImpact = (key: string) => {
        const points: Record<string, number> = {
            improve_security_headers: 10,
            enforce_https: 12,
            renew_ssl_certificate: 9,
            configure_email_security: 8,
            review_dns_configuration: 7,
            renew_domain_early: 6,
            check_whois_visibility: 4,
            maintain_current_posture: 0,
        };

        return points[key] ?? 3;
    };

    const recommendationsWithImpact = computed(() => {
        if (!result.value) {
            return [];
        }

        return result.value.overview.recommendations.map((item) => ({
            key: item,
            label: recommendationLabel(item),
            impact: recommendationImpact(item),
        }));
    });

    const emailSecurityScore = computed(() => {
        if (!result.value) {
            return 0;
        }

        const emailSecurity = result.value.domainLite.dns.emailSecurity;
        let score = 0;

        if (emailSecurity.hasSpf) {
            score += 1;
        }

        if (emailSecurity.hasDmarc) {
            score += 1;
        }

        return score;
    });

    const domainAgeDays = computed(() => {
        if (!result.value) {
            return null;
        }

        const createdAt = result.value.domainLite.whois.createdAt;

        if (!createdAt) {
            return null;
        }

        const created = new Date(createdAt).getTime();

        if (Number.isNaN(created)) {
            return null;
        }

        const now = Date.now();
        const diff = now - created;

        return diff < 0 ? 0 : Math.floor(diff / (1000 * 60 * 60 * 24));
    });

    const totalResponseTimeMs = computed(() => {
        if (!result.value) {
            return 0;
        }

        return result.value.siteHealth.http.chain.reduce((sum, step) => sum + step.responseTimeMs, 0);
    });

    return {
        scoreBadgeClass,
        formatDateTime,
        signalLabel,
        clampPercent,
        scoreBars,
        dnsBars,
        httpLatencyBars,
        signalBars,
        recommendationsWithImpact,
        emailSecurityScore,
        domainAgeDays,
        totalResponseTimeMs,
    };
};
