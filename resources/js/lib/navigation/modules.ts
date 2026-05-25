import {
    AtSign,
    BookOpenText,
    Building2,
    FileSearch,
    Fingerprint,
    Globe,
    LayoutGrid,
    MailSearch,
    Newspaper,
    Radar,
    Send,
    User,
    Youtube,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

type Translate = (key: string) => string;

type ModuleNavDefinition = {
    key: string;
    labelKey: string;
    href: NavItem['href'];
    icon: NonNullable<NavItem['icon']>;
    showInHeader?: boolean;
};

const moduleNavDefinitions: readonly ModuleNavDefinition[] = [
    {
        key: 'dashboard',
        labelKey: 'navigation.dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        showInHeader: true,
    },
    {
        key: 'telegram',
        labelKey: 'navigation.telegram',
        href: '/telegram',
        icon: Send,
        showInHeader: true,
    },
    {
        key: 'youtube',
        labelKey: 'navigation.youtube',
        href: '/youtube',
        icon: Youtube,
        showInHeader: true,
    },
    {
        key: 'username',
        labelKey: 'navigation.username',
        href: '/username',
        icon: AtSign,
        showInHeader: true,
    },
    {
        key: 'site-intel',
        labelKey: 'navigation.siteIntel',
        href: '/site-intel',
        icon: Radar,
    },
    {
        key: 'fio',
        labelKey: 'navigation.fio',
        href: '/fio',
        icon: User,
    },
    {
        key: 'company-intel',
        labelKey: 'navigation.companyIntel',
        href: '/company-intel',
        icon: Building2,
    },
    {
        key: 'document-intel',
        labelKey: 'navigation.documentIntel',
        href: '/document-intel',
        icon: FileSearch,
    },
    {
        key: 'email-intel',
        labelKey: 'navigation.emailIntel',
        href: '/email-intel',
        icon: MailSearch,
    },
    {
        key: 'news-media-intel',
        labelKey: 'navigation.newsMediaIntel',
        href: '/news-media-intel',
        icon: Newspaper,
    },
    {
        key: 'domain-infra-intel',
        labelKey: 'navigation.domainInfraIntel',
        href: '/domain-infra-intel',
        icon: Globe,
    },
    {
        key: 'shifr',
        labelKey: 'navigation.shifr',
        href: '/shifr',
        icon: Fingerprint,
    },
];

const toNavItem = (t: Translate, item: ModuleNavDefinition): NavItem => ({
    title: t(item.labelKey),
    href: item.href,
    icon: item.icon,
});

export const buildMainNavItems = (t: Translate): NavItem[] =>
    moduleNavDefinitions.map((item) => toNavItem(t, item));

export const buildHeaderNavItems = (t: Translate): NavItem[] =>
    moduleNavDefinitions
        .filter((item) => item.showInHeader)
        .map((item) => toNavItem(t, item));

export const buildFooterNavItems = (t: Translate): NavItem[] => [
    {
        title: t('navigation.modulesWiki'),
        href: '/wiki/modules',
        icon: BookOpenText,
    },
];
