import BlueskyAnalyticsReportsController from './BlueskyAnalyticsReportsController'
import BlueskyAnalyticsController from './BlueskyAnalyticsController'
import BlueskySearchController from './BlueskySearchController'
import BlueskyParserController from './BlueskyParserController'
const Bluesky = {
    BlueskyAnalyticsReportsController: Object.assign(BlueskyAnalyticsReportsController, BlueskyAnalyticsReportsController),
BlueskyAnalyticsController: Object.assign(BlueskyAnalyticsController, BlueskyAnalyticsController),
BlueskySearchController: Object.assign(BlueskySearchController, BlueskySearchController),
BlueskyParserController: Object.assign(BlueskyParserController, BlueskyParserController),
}

export default Bluesky