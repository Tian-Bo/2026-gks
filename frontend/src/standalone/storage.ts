export function getStore(key: string) {
  const value = window.localStorage.getItem(key)
  if (value !== null)
    return value

  // Standalone AI page has no merchant-login shell to inject the active shop.
  return key === 'shop_id' ? '1' : null
}

export function setStore(key: string, value: string | number) {
  window.localStorage.setItem(key, String(value))
}
