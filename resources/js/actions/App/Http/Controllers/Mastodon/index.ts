import MastodonAnalyticsReportsController from './MastodonAnalyticsReportsController'
import MastodonAnalyticsController from './MastodonAnalyticsController'
import MastodonSearchController from './MastodonSearchController'
import MastodonParserController from './MastodonParserController'
const Mastodon = {
    MastodonAnalyticsReportsController: Object.assign(MastodonAnalyticsReportsController, MastodonAnalyticsReportsController),
MastodonAnalyticsController: Object.assign(MastodonAnalyticsController, MastodonAnalyticsController),
MastodonSearchController: Object.assign(MastodonSearchController, MastodonSearchController),
MastodonParserController: Object.assign(MastodonParserController, MastodonParserController),
}

export default Mastodon