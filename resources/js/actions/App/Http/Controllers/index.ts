import DashboardController from './DashboardController'
import Dashboard from './Dashboard'
import Telegram from './Telegram'
import Mastodon from './Mastodon'
import SiteIntel from './SiteIntel'
import NewsMediaIntel from './NewsMediaIntel'
import Shifr from './Shifr'
import YouTube from './YouTube'
import Wiki from './Wiki'
import Settings from './Settings'
const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
Dashboard: Object.assign(Dashboard, Dashboard),
Telegram: Object.assign(Telegram, Telegram),
Mastodon: Object.assign(Mastodon, Mastodon),
SiteIntel: Object.assign(SiteIntel, SiteIntel),
NewsMediaIntel: Object.assign(NewsMediaIntel, NewsMediaIntel),
Shifr: Object.assign(Shifr, Shifr),
YouTube: Object.assign(YouTube, YouTube),
Wiki: Object.assign(Wiki, Wiki),
Settings: Object.assign(Settings, Settings),
}

export default Controllers