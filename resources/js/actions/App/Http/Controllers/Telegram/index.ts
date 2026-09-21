import TelegramTrackingController from './TelegramTrackingController'
import TelegramSearchController from './TelegramSearchController'
import TelegramAnalyticsController from './TelegramAnalyticsController'
import TelegramParserController from './TelegramParserController'
const Telegram = {
    TelegramTrackingController: Object.assign(TelegramTrackingController, TelegramTrackingController),
TelegramSearchController: Object.assign(TelegramSearchController, TelegramSearchController),
TelegramAnalyticsController: Object.assign(TelegramAnalyticsController, TelegramAnalyticsController),
TelegramParserController: Object.assign(TelegramParserController, TelegramParserController),
}

export default Telegram