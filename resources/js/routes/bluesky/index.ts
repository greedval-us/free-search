import analytics from './analytics'
import search from './search'
import parser from './parser'
import posts from './posts'
import actors from './actors'
const bluesky = {
    analytics: Object.assign(analytics, analytics),
search: Object.assign(search, search),
parser: Object.assign(parser, parser),
posts: Object.assign(posts, posts),
actors: Object.assign(actors, actors),
}

export default bluesky