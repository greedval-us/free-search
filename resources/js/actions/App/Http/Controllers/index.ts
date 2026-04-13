import Gdelt from './Gdelt'
import Telegram from './Telegram'
import Settings from './Settings'
const Controllers = {
    Gdelt: Object.assign(Gdelt, Gdelt),
Telegram: Object.assign(Telegram, Telegram),
Settings: Object.assign(Settings, Settings),
}

export default Controllers