import Telegram from './Telegram'
import Username from './Username'
import SiteIntel from './SiteIntel'
import Settings from './Settings'
const Controllers = {
    Telegram: Object.assign(Telegram, Telegram),
Username: Object.assign(Username, Username),
SiteIntel: Object.assign(SiteIntel, SiteIntel),
Settings: Object.assign(Settings, Settings),
}

export default Controllers