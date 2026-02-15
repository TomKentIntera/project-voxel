export function getErrorMessage(error, fallbackMessage) {
  if (!error || typeof error !== 'object') {
    return fallbackMessage
  }

  if ('payload' in error && error.payload && typeof error.payload === 'object') {
    if ('message' in error.payload && typeof error.payload.message === 'string') {
      return error.payload.message
    }

    if ('errors' in error.payload && error.payload.errors && typeof error.payload.errors === 'object') {
      const errorList = Object.values(error.payload.errors).flat()
      if (errorList.length > 0 && typeof errorList[0] === 'string') {
        return errorList[0]
      }
    }
  }

  if ('message' in error && typeof error.message === 'string') {
    return error.message
  }

  return fallbackMessage
}
