import search from './search'
import statuses from './statuses'
import accounts from './accounts'
const mastodon = {
    search: Object.assign(search, search),
statuses: Object.assign(statuses, statuses),
accounts: Object.assign(accounts, accounts),
}

export default mastodon