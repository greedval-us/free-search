import Telegram from './Telegram'
import Username from './Username'
import SiteIntel from './SiteIntel'
import Fio from './Fio'
import Dorks from './Dorks'
import Settings from './Settings'
const Controllers = {
    Telegram: Object.assign(Telegram, Telegram),
Username: Object.assign(Username, Username),
SiteIntel: Object.assign(SiteIntel, SiteIntel),
Fio: Object.assign(Fio, Fio),
Dorks: Object.assign(Dorks, Dorks),
Settings: Object.assign(Settings, Settings),
}

export default Controllers