import BlueskyAnalyticsController from './BlueskyAnalyticsController'
import BlueskySearchController from './BlueskySearchController'
import BlueskyParserController from './BlueskyParserController'
const Bluesky = {
    BlueskyAnalyticsController: Object.assign(BlueskyAnalyticsController, BlueskyAnalyticsController),
BlueskySearchController: Object.assign(BlueskySearchController, BlueskySearchController),
BlueskyParserController: Object.assign(BlueskyParserController, BlueskyParserController),
}

export default Bluesky