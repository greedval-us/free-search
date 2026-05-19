import DashboardController from './DashboardController'
import Dashboard from './Dashboard'
import Telegram from './Telegram'
import Username from './Username'
import SiteIntel from './SiteIntel'
import CompanyIntel from './CompanyIntel'
import DocumentIntel from './DocumentIntel'
import Fio from './Fio'
import EmailIntel from './EmailIntel'
import DomainInfraIntel from './DomainInfraIntel'
import NewsMediaIntel from './NewsMediaIntel'
import Shifr from './Shifr'
import Wiki from './Wiki'
import Settings from './Settings'
const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
Dashboard: Object.assign(Dashboard, Dashboard),
Telegram: Object.assign(Telegram, Telegram),
Username: Object.assign(Username, Username),
SiteIntel: Object.assign(SiteIntel, SiteIntel),
CompanyIntel: Object.assign(CompanyIntel, CompanyIntel),
DocumentIntel: Object.assign(DocumentIntel, DocumentIntel),
Fio: Object.assign(Fio, Fio),
EmailIntel: Object.assign(EmailIntel, EmailIntel),
DomainInfraIntel: Object.assign(DomainInfraIntel, DomainInfraIntel),
NewsMediaIntel: Object.assign(NewsMediaIntel, NewsMediaIntel),
Shifr: Object.assign(Shifr, Shifr),
Wiki: Object.assign(Wiki, Wiki),
Settings: Object.assign(Settings, Settings),
}

export default Controllers