import { apiRequest } from './apiClient'

export interface LocationCacheNode {
  id: number
  name: string
  fqdn: string
  memory: number
  location: string
  memoryAllocated: number
  memoryUsedPercent: number
  memoryFree: number
}

export interface LocationCacheLocation {
  id: number
  short: string
  long: string
  nodeCount: number
  totalMemory: number
  totalUsedMemory: number
  totalFreeMemory: number
  totalMemoryUsedPercent: number
  maxFreeMemory: number
  memoryUsedFreestNodePercent: number
  totalMemoryGB: number
  totalUsedMemoryGB: number
  totalFreeMemoryGB: number
  maxFreeMemoryGB: number
}

export interface LocationsCachePayload {
  locations: LocationCacheLocation[]
  nodes: LocationCacheNode[]
}

interface LocationsCacheResponse {
  data: LocationsCachePayload
  meta: {
    disk: string
    path: string
    location_count: number
    node_count: number
  }
}

export function fetchLocationsCache(
  token: string,
): Promise<LocationsCacheResponse> {
  return apiRequest<LocationsCacheResponse>('/api/locations/cache', { token })
}

export function fetchLocationsCacheRaw(
  token: string,
): Promise<LocationsCachePayload> {
  return apiRequest<LocationsCachePayload>('/api/locations/cache/raw', { token })
}

