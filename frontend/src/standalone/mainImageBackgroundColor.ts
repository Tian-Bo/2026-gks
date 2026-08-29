type RgbaColor = {
  r: number
  g: number
  b: number
  a: number
}

type HslColor = {
  h: number
  s: number
  l: number
}

type BackgroundColors = {
  pageBackground: string
}

type BackgroundColorResult = {
  colors: BackgroundColors
  source: 'sampled' | 'fallback'
  reason?: string
}

const DEFAULT_SEED_COLOR = '#E62222'
const MAX_SAMPLE_WIDTH = 160
const SAMPLE_STEP = 3

function clamp(value: number, min: number, max: number) {
  return Math.min(max, Math.max(min, value))
}

function componentToHex(value: number) {
  return Math.round(clamp(value, 0, 255)).toString(16).padStart(2, '0')
}

function rgbToHex(color: Pick<RgbaColor, 'r' | 'g' | 'b'>) {
  return `#${componentToHex(color.r)}${componentToHex(color.g)}${componentToHex(color.b)}`.toUpperCase()
}

function hslToHex(color: HslColor) {
  const h = ((color.h % 360) + 360) % 360
  const s = clamp(color.s, 0, 100) / 100
  const l = clamp(color.l, 0, 100) / 100
  const c = (1 - Math.abs(2 * l - 1)) * s
  const x = c * (1 - Math.abs(((h / 60) % 2) - 1))
  const m = l - c / 2
  let r = 0
  let g = 0
  let b = 0

  if (h < 60) { r = c; g = x }
  else if (h < 120) { r = x; g = c }
  else if (h < 180) { g = c; b = x }
  else if (h < 240) { g = x; b = c }
  else if (h < 300) { r = x; b = c }
  else { r = c; b = x }

  return rgbToHex({ r: (r + m) * 255, g: (g + m) * 255, b: (b + m) * 255 })
}

function rgbToHsl(color: RgbaColor): HslColor {
  const r = color.r / 255
  const g = color.g / 255
  const b = color.b / 255
  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const delta = max - min
  const l = (max + min) / 2
  const s = delta === 0 ? 0 : delta / (1 - Math.abs(2 * l - 1))
  let h = 0

  if (delta !== 0) {
    if (max === r) h = 60 * (((g - b) / delta) % 6)
    else if (max === g) h = 60 * ((b - r) / delta + 2)
    else h = 60 * ((r - g) / delta + 4)
  }

  return { h: h < 0 ? h + 360 : h, s: s * 100, l: l * 100 }
}

function getDefaultResult(reason: string): BackgroundColorResult {
  const seed = rgbToHsl({ r: 230, g: 34, b: 34, a: 255 })
  return {
    source: 'fallback',
    reason,
    colors: { pageBackground: hslToHex({ h: seed.h, s: clamp(seed.s - 7, 72, 88), l: 25 }) },
  }
}

function isValidCandidate(color: RgbaColor, hsl: HslColor) {
  return color.a >= 200 && hsl.s >= 25 && hsl.l <= 88 && hsl.l >= 12
}

function getSeedColor(colors: HslColor[]) {
  const buckets = new Map<string, { count: number, h: number, s: number, l: number }>()
  colors.forEach((color) => {
    const key = `${Math.floor(color.h / 8) * 8}-${Math.floor(color.s / 8) * 8}-${Math.floor(color.l / 8) * 8}`
    const bucket = buckets.get(key) || { count: 0, h: 0, s: 0, l: 0 }
    bucket.count += 1
    bucket.h += color.h
    bucket.s += color.s
    bucket.l += color.l
    buckets.set(key, bucket)
  })

  let winner: { color: HslColor, score: number } | null = null
  buckets.forEach((bucket) => {
    const color = { h: bucket.h / bucket.count, s: bucket.s / bucket.count, l: bucket.l / bucket.count }
    const areaRatio = bucket.count / colors.length
    const midLightnessScore = Math.max(0, 1 - Math.abs(color.l / 100 - 0.55) / 0.55)
    const score = Math.pow(areaRatio, 0.72) * Math.pow(color.s / 100, 1.4) * midLightnessScore
    if (!winner || score > winner.score)
      winner = { color, score }
  })

  return winner?.color || null
}

export async function resolveMainImageBackgroundColors(imageUrl: string): Promise<BackgroundColorResult> {
  if (!imageUrl)
    return getDefaultResult('image-empty')

  const image = await new Promise<HTMLImageElement | null>((resolve) => {
    const value = new Image()
    value.crossOrigin = 'anonymous'
    value.onload = () => resolve(value)
    value.onerror = () => resolve(null)
    value.src = imageUrl
  })
  if (!image || !image.naturalWidth || !image.naturalHeight)
    return getDefaultResult('image-load-failed')

  const width = MAX_SAMPLE_WIDTH
  const height = Math.max(1, Math.round(image.naturalHeight / image.naturalWidth * width))
  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height
  const context = canvas.getContext('2d', { willReadFrequently: true })
  if (!context)
    return getDefaultResult('canvas-unavailable')

  try {
    context.drawImage(image, 0, 0, width, height)
    const { data } = context.getImageData(0, 0, width, height)
    const colors: HslColor[] = []
    for (let y = 0; y < height; y += SAMPLE_STEP) {
      for (let x = 0; x < width; x += SAMPLE_STEP) {
        const index = (y * width + x) * 4
        const rgba = { r: data[index], g: data[index + 1], b: data[index + 2], a: data[index + 3] }
        const hsl = rgbToHsl(rgba)
        if (isValidCandidate(rgba, hsl))
          colors.push(hsl)
      }
    }

    const seed = getSeedColor(colors)
    if (!seed)
      return getDefaultResult('no-valid-colors')

    return {
      source: 'sampled',
      colors: { pageBackground: hslToHex({ h: seed.h, s: clamp(seed.s - 7, 72, 88), l: 25 }) },
    }
  } catch {
    return getDefaultResult('canvas-read-failed')
  }
}

export { DEFAULT_SEED_COLOR }
