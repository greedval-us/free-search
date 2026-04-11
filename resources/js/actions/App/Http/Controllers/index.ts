import Telegram from './Telegram'
import Settings from './Settings'
const Controllers = {
    Telegram: Object.assign(Telegram, Telegram),
Settings: Object.assign(Settings, Settings),
}

export default Controllers