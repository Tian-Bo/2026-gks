import { message } from 'ant-design-vue'

export const klbMessage = {
  info: (content: string) => message.info(content),
  success: (content: string) => message.success(content),
  warning: (content: string) => message.warning(content),
  error: (content: string) => message.error(content),
}
