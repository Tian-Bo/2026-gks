const unavailable = async <T = unknown, R = unknown>(_url?: string, _data?: T, _config?: unknown): Promise<R> => {
  throw new Error('独立展示模式未配置请求服务')
}

export default {
  get: unavailable,
  post: unavailable,
  put: unavailable,
  delete: unavailable,
}
