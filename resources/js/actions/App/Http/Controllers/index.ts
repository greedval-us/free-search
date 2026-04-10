import TelegramSearchController from './TelegramSearchController'
import Telegram from './Telegram'
import Settings from './Settings'
const Controllers = {
    TelegramSearchController: Object.assign(TelegramSearchController, TelegramSearchController),
Telegram: Object.assign(Telegram, Telegram),
Settings: Object.assign(Settings, Settings),
}

export default Controllers