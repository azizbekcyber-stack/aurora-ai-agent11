export default defineNuxtRouteMiddleware((to) => {
  if (!to.path.startsWith('/dashboard')) {
    return
  }

  const { sessionToken, dashboardToken } = useApi()

  if (!sessionToken.value && !dashboardToken.value) {
    return navigateTo('/login')
  }
})
