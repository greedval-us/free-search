import TelegramSearchController from './TelegramSearchController'
import TelegramAnalyticsController from './TelegramAnalyticsController'
import TelegramParserController from './TelegramParserController'
const Telegram = {
    TelegramSearchController: Object.assign(TelegramSearchController, TelegramSearchController),
TelegramAnalyticsController: Object.assign(TelegramAnalyticsController, TelegramAnalyticsController),
TelegramParserController: Object.assign(TelegramParserController, TelegramParserController),
}

export default Telegram