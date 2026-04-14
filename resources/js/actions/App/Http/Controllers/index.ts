import Telegram from './Telegram'
import Username from './Username'
import Settings from './Settings'
const Controllers = {
    Telegram: Object.assign(Telegram, Telegram),
Username: Object.assign(Username, Username),
Settings: Object.assign(Settings, Settings),
}

export default Controllers