export type YouTubeTabValue = 'search' | 'analytics' | 'parser'

export type YouTubeVideo = {
  id: string
  title: string
  description: string
  channelId: string
  channelTitle: string
  publishedAt: string
  thumbnail: string
  duration: string
  views: number
  likes: number
  comments: number
  url: string
}

export type YouTubeSearchPayload = {
  items: YouTubeVideo[]
  pagination: {
    nextPageToken: string | null
    total: number
    perPage: number
  }
}

export type YouTubeAnalyticsPayload = {
  mode: 'video' | 'channel'
  video: YouTubeVideo | null
  totals: {
    videos: number
    views: number
    likes: number
    comments: number
    avgViews: number
    engagementRate: number
  }
  topVideos: YouTubeVideo[]
}

export type YouTubeCommentItem = {
  id: string
  threadId: string
  author: string
  authorChannelUrl: string
  text: string
  likeCount: number
  publishedAt: string
  replyCount: number
  replies: Array<{
    id: string
    author: string
    text: string
    likeCount: number
    publishedAt: string
  }>
}

export type YouTubeCommentsPayload = {
  items: YouTubeCommentItem[]
  pagination: {
    nextPageToken: string | null
    total: number
    perPage: number
  }
}
