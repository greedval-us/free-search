import search from './search'
import statuses from './statuses'
import accounts from './accounts'
import tags from './tags'
const mastodon = {
    search: Object.assign(search, search),
statuses: Object.assign(statuses, statuses),
accounts: Object.assign(accounts, accounts),
tags: Object.assign(tags, tags),
}

export default mastodon