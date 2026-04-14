import Username from './Username'
import Telegram from './Telegram'
import Settings from './Settings'
const Controllers = {
    Username: Object.assign(Username, Username),
Telegram: Object.assign(Telegram, Telegram),
Settings: Object.assign(Settings, Settings),
}

export default Controllers