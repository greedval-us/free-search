import YouTubeSearchController from './YouTubeSearchController'
import YouTubeParserController from './YouTubeParserController'
import YouTubeAnalyticsReportsController from './YouTubeAnalyticsReportsController'
import YouTubeAnalyticsController from './YouTubeAnalyticsController'
const YouTube = {
    YouTubeSearchController: Object.assign(YouTubeSearchController, YouTubeSearchController),
YouTubeParserController: Object.assign(YouTubeParserController, YouTubeParserController),
YouTubeAnalyticsReportsController: Object.assign(YouTubeAnalyticsReportsController, YouTubeAnalyticsReportsController),
YouTubeAnalyticsController: Object.assign(YouTubeAnalyticsController, YouTubeAnalyticsController),
}

export default YouTube