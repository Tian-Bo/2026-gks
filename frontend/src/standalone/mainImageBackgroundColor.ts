export async function resolveMainImageBackgroundColors(_imageUrl: string) {
  return {
    source: 'fallback',
    reason: 'standalone-preview',
    colors: { pageBackground: '#FFF1F1', main: '#E62222', secondary: '#FFF1F1' },
  }
}
