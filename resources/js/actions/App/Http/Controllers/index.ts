import DashboardController from './DashboardController'
import Dashboard from './Dashboard'
import Telegram from './Telegram'
import Username from './Username'
import SiteIntel from './SiteIntel'
import Fio from './Fio'
import EmailIntel from './EmailIntel'
import Settings from './Settings'
const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
Dashboard: Object.assign(Dashboard, Dashboard),
Telegram: Object.assign(Telegram, Telegram),
Username: Object.assign(Username, Username),
SiteIntel: Object.assign(SiteIntel, SiteIntel),
Fio: Object.assign(Fio, Fio),
EmailIntel: Object.assign(EmailIntel, EmailIntel),
Settings: Object.assign(Settings, Settings),
}

export default Controllers