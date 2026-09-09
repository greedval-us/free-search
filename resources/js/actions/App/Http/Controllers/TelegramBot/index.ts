import WebhookController from './WebhookController'
import SettingsController from './SettingsController'
const TelegramBot = {
    WebhookController: Object.assign(WebhookController, WebhookController),
SettingsController: Object.assign(SettingsController, SettingsController),
}

export default TelegramBot