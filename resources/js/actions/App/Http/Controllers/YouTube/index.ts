import YouTubeSearchController from './YouTubeSearchController'
import YouTubeAnalyticsController from './YouTubeAnalyticsController'
import YouTubeParserController from './YouTubeParserController'
const YouTube = {
    YouTubeSearchController: Object.assign(YouTubeSearchController, YouTubeSearchController),
YouTubeAnalyticsController: Object.assign(YouTubeAnalyticsController, YouTubeAnalyticsController),
YouTubeParserController: Object.assign(YouTubeParserController, YouTubeParserController),
}

export default YouTube