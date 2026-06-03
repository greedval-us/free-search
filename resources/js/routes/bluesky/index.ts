import search from './search'
import posts from './posts'
const bluesky = {
    search: Object.assign(search, search),
posts: Object.assign(posts, posts),
}

export default bluesky