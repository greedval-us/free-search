import TelegramTrackingController from './TelegramTrackingController'
import TelegramSearchController from './TelegramSearchController'
import TelegramAnalyticsReportsController from './TelegramAnalyticsReportsController'
import TelegramAnalyticsController from './TelegramAnalyticsController'
import TelegramParserController from './TelegramParserController'
const Telegram = {
    TelegramTrackingController: Object.assign(TelegramTrackingController, TelegramTrackingController),
TelegramSearchController: Object.assign(TelegramSearchController, TelegramSearchController),
TelegramAnalyticsReportsController: Object.assign(TelegramAnalyticsReportsController, TelegramAnalyticsReportsController),
TelegramAnalyticsController: Object.assign(TelegramAnalyticsController, TelegramAnalyticsController),
TelegramParserController: Object.assign(TelegramParserController, TelegramParserController),
}

export default Telegram