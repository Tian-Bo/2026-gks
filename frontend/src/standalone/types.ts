export type AiMessageStatus = 'success' | 'pending' | 'streaming' | 'completed' | 'stopped' | 'error'
export type MerchantItemType = 'voucher' | 'bundle' | 'stored_value'

export type AiConversation = Record<string, any>
export type AiGeneratedActivity = Record<string, any>
export type AiGeneratedPoster = Record<string, any>
export type AiPosterImagePreviewCard = Record<string, any> & {
  card_id: string
  type: 'poster_image_preview'
}
export type AiPromptTipItem = Record<string, any>
export type AiInspirationItem = Record<string, any>
export type AiMessage = Record<string, any>
export type UnifiedItem = Record<string, any>
export type AiStreamEventBase = Record<string, any>
export type AiStreamActivityGeneratedEvent = Record<string, any>
export type AiStreamActivityImageProgressEvent = Record<string, any>
export type AiStreamCompletedEvent = Record<string, any>
export type AiStreamDoneEvent = Record<string, any>
export type AiStreamErrorEvent = Record<string, any>
export type AiStreamMessageCardEvent = Record<string, any>
export type AiStreamPosterGeneratedEvent = Record<string, any>
export type AiStreamPosterProgressEvent = Record<string, any>
