import search from './search';
import analytics from './analytics';
import parser from './parser';
const youtube = {
    search: Object.assign(search, search),
    analytics: Object.assign(analytics, analytics),
    parser: Object.assign(parser, parser),
};

export default youtube;
