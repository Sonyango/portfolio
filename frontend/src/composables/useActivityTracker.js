import { onMounted, onUnmounted } from "vue";
import { useAuthStore } from "@/stores/authStore";
import api from "@/api/index.js";

export function useActivityTracker() {
  const authStore = useAuthStore()
  const IDLE_TIMEOUT_MS = 15 * 60 * 1000
  const PING_INTERVAL_MS = 30 * 1000
  const ACTIVE_WINDOW_MS = 60 * 1000
  const ACTIVITY_STORAGE_KEY = 'admin_last_activity_at'

  let lastInteractionAt = 0
  let lastSyncedActivityAt = 0
  let lastPingAt = 0
  let idleTimeout = null
  let pingInterval = null
  let activityChannel = null
  let expiring = false

  function clearLocalSession(reason = 'inactivity', broadcast = true) {
    localStorage.removeItem('admin_token')
    localStorage.removeItem(ACTIVITY_STORAGE_KEY)
    authStore.user = null

    if (broadcast) activityChannel?.postMessage({ type: 'session-ended', reason })

    if (!window.location.pathname.includes('/admin/login')) {
      window.location.replace(`/admin/login?reason=${reason}`)
    }
  }

  async function expireSession() {
    if (expiring) return
    expiring = true

    try {
      // Revoke the server token too; middleware rejects it if already expired.
      await api.post('/admin/logout')
    } catch {
      // Still clear the local session if the server cannot be reached.
    } finally {
      clearLocalSession()
    }
  }

  function scheduleIdleTimeout() {
    clearTimeout(idleTimeout)
    const remaining = IDLE_TIMEOUT_MS - (Date.now() - lastInteractionAt)

    if (remaining <= 0) {
      void expireSession()
      return
    }

    idleTimeout = setTimeout(() => {
      if (Date.now() - lastInteractionAt >= IDLE_TIMEOUT_MS) {
        void expireSession()
      } else {
        scheduleIdleTimeout()
      }
    }, remaining)
  }

  function recordActivity(timestamp = Date.now(), broadcast = true) {
    if (!authStore.isAuthenticated || expiring) return

    const activityAt = Number(timestamp)
    if (!Number.isFinite(activityAt)) return

    lastInteractionAt = Math.max(lastInteractionAt, activityAt)
    scheduleIdleTimeout()

    if (lastInteractionAt - lastSyncedActivityAt >= 1000) {
      localStorage.setItem(ACTIVITY_STORAGE_KEY, String(lastInteractionAt))
      lastSyncedActivityAt = lastInteractionAt

      if (broadcast) {
        activityChannel?.postMessage({ type: 'activity', timestamp: lastInteractionAt })
      }
    }
  }

  function onUserActivity() {
    recordActivity()
  }

  async function sendActivityPing() {
    if (!authStore.isAuthenticated || expiring) return

    lastPingAt = Date.now()

    try {
      await api.post('/admin/activity-check')
    } catch (error) {
      if (error.response?.status === 401) {
        clearLocalSession(error.response.data?.session_expired ? 'inactivity' : 'unauthorized')
      }
    }
  }

  function sendPingWhileActive() {
    const now = Date.now()
    const recentlyActive = now - lastInteractionAt < ACTIVE_WINDOW_MS

    if (recentlyActive && now - lastPingAt >= PING_INTERVAL_MS) {
      void sendActivityPing()
    }
  }

  // User interaction events to track
  const activityEvents = [
    'mousedown',
    'mousemove',
    'keydown',
    'touchstart',
    'scroll',
    'click',
  ]

  function onActivityMessage(event) {
    if (event.data?.type === 'activity') {
      recordActivity(event.data.timestamp, false)
    } else if (event.data?.type === 'session-ended') {
      clearLocalSession(event.data.reason, false)
    }
  }

  function onStorageActivity(event) {
    if (event.key === ACTIVITY_STORAGE_KEY && event.newValue) {
      recordActivity(Number(event.newValue), false)
    }
  }

  function onVisibilityChange() {
    if (document.visibilityState === 'visible') scheduleIdleTimeout()
  }

  onMounted(() => {
    if (!authStore.isAuthenticated) return

    const storedActivity = Number(localStorage.getItem(ACTIVITY_STORAGE_KEY))
    lastInteractionAt = Number.isFinite(storedActivity) && storedActivity > 0
      ? storedActivity
      : Date.now()
    lastSyncedActivityAt = lastInteractionAt
    lastPingAt = Date.now()

    activityEvents.forEach(event => {
      window.addEventListener(event, onUserActivity, { passive: true })
    })

    if ('BroadcastChannel' in window) {
      activityChannel = new BroadcastChannel('admin-session-activity')
      activityChannel.addEventListener('message', onActivityMessage)
    }

    window.addEventListener('storage', onStorageActivity)
    document.addEventListener('visibilitychange', onVisibilityChange)

    scheduleIdleTimeout()
    pingInterval = setInterval(sendPingWhileActive, PING_INTERVAL_MS)
  })

  onUnmounted(() => {
    activityEvents.forEach(event => {
      window.removeEventListener(event, onUserActivity)
    })
    clearTimeout(idleTimeout)
    clearInterval(pingInterval)
    window.removeEventListener('storage', onStorageActivity)
    document.removeEventListener('visibilitychange', onVisibilityChange)
    activityChannel?.removeEventListener('message', onActivityMessage)
    activityChannel?.close()
  })

  return { onUserActivity }
}
