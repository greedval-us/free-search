import YouTubeSearchController from './YouTubeSearchController'
import YouTubeParserController from './YouTubeParserController'
import YouTubeAnalyticsController from './YouTubeAnalyticsController'
const YouTube = {
    YouTubeSearchController: Object.assign(YouTubeSearchController, YouTubeSearchController),
YouTubeParserController: Object.assign(YouTubeParserController, YouTubeParserController),
YouTubeAnalyticsController: Object.assign(YouTubeAnalyticsController, YouTubeAnalyticsController),
}

export default YouTube