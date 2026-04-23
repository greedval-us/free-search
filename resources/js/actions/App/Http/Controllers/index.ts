import Telegram from './Telegram'
import Username from './Username'
import SiteIntel from './SiteIntel'
import Fio from './Fio'
import Settings from './Settings'
const Controllers = {
    Telegram: Object.assign(Telegram, Telegram),
Username: Object.assign(Username, Username),
SiteIntel: Object.assign(SiteIntel, SiteIntel),
Fio: Object.assign(Fio, Fio),
Settings: Object.assign(Settings, Settings),
}

export default Controllers