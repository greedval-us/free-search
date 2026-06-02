import search from './search'
import statuses from './statuses'
const mastodon = {
    search: Object.assign(search, search),
statuses: Object.assign(statuses, statuses),
}

export default mastodon