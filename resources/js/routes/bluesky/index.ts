import analytics from './analytics'
import search from './search'
import posts from './posts'
import actors from './actors'
const bluesky = {
    analytics: Object.assign(analytics, analytics),
search: Object.assign(search, search),
posts: Object.assign(posts, posts),
actors: Object.assign(actors, actors),
}

export default bluesky