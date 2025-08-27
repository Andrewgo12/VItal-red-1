import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
// import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m.js'
import '../css/app.css'

// Global components
import Layout from './Layouts/AppLayout.vue'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Vital Red'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        )

        // Set default layout for all pages
        page.then((module) => {
            if (!module.default.layout) {
                module.default.layout = Layout
            }
        })

        return page
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            // .use(ZiggyVue, Ziggy)

        // Global properties
        // Simple route function for development
        window.route = window.route || function(name, params = {}) {
            const routes = {
                'dashboard': '/dashboard',
                'login': '/login',
                'logout': '/logout',
                'register': '/register',
                'password.request': '/forgot-password',
                'profile.edit': '/profile',
                'medico.bandeja-casos': '/medico/bandeja-casos',
                'medico.casos-urgentes': '/medico/casos-urgentes',
                'medico.evaluar-solicitud': '/medico/evaluar-solicitud',
                'solicitudes-medicas.create': '/solicitudes-medicas/create',
                'admin.users.index': '/admin/users',
                'admin.reports.index': '/admin/reports',
                'admin.config': '/admin/config',
                'admin.system-status': '/admin/system-status',
                'notifications.index': '/notifications',
                'notifications.mark-all-read': '/notifications/mark-all-read',
                'settings.notifications': '/settings/notifications',
                'settings.appearance': '/settings/appearance'
            }

            let url = routes[name] || '/'

            // Simple parameter replacement
            if (params.id) {
                url = url.replace('{id}', params.id)
            }

            return url
        }

        app.config.globalProperties.$route = window.route
        // Global utility functions
        window.formatDate = window.formatDate || function(date, format = 'DD/MM/YYYY') {
            if (!date) return ''
            const d = new Date(date)
            return d.toLocaleDateString('es-ES')
        }

        window.showToast = window.showToast || function(message, type = 'info') {
            console.log(`Toast [${type}]: ${message}`)
        }

        window.showConfirm = window.showConfirm || function(title, text) {
            return Promise.resolve({ isConfirmed: confirm(`${title}\n${text}`) })
        }

        window.showLoading = window.showLoading || function() {
            console.log('Loading...')
        }

        window.hideLoading = window.hideLoading || function() {
            console.log('Loading finished')
        }

        app.config.globalProperties.$formatDate = window.formatDate
        app.config.globalProperties.$showToast = window.showToast
        app.config.globalProperties.$showConfirm = window.showConfirm
        app.config.globalProperties.$showLoading = window.showLoading
        app.config.globalProperties.$hideLoading = window.hideLoading

        // Global mixins
        app.mixin({
            methods: {
                // Format currency
                formatCurrency(amount, currency = 'COP') {
                    return new Intl.NumberFormat('es-CO', {
                        style: 'currency',
                        currency: currency
                    }).format(amount)
                },

                // Format number
                formatNumber(number) {
                    return new Intl.NumberFormat('es-CO').format(number)
                },

                // Get priority badge class
                getPriorityClass(priority) {
                    const classes = {
                        'Alta': 'badge-urgent',
                        'Media': 'badge-medium',
                        'Baja': 'badge-low'
                    }
                    return classes[priority] || 'bg-secondary'
                },

                // Get status badge class
                getStatusClass(status) {
                    const classes = {
                        'pendiente_evaluacion': 'bg-warning',
                        'en_evaluacion': 'bg-info',
                        'aceptada': 'bg-success',
                        'rechazada': 'bg-danger',
                        'derivada': 'bg-secondary',
                        'completada': 'bg-primary'
                    }
                    return classes[status] || 'bg-secondary'
                },

                // Get status text
                getStatusText(status) {
                    const texts = {
                        'pendiente_evaluacion': 'Pendiente Evaluación',
                        'en_evaluacion': 'En Evaluación',
                        'aceptada': 'Aceptada',
                        'rechazada': 'Rechazada',
                        'derivada': 'Derivada',
                        'completada': 'Completada'
                    }
                    return texts[status] || status
                },

                // Get urgency score color
                getUrgencyColor(score) {
                    if (score >= 80) return 'text-danger'
                    if (score >= 50) return 'text-warning'
                    return 'text-success'
                },

                // Truncate text
                truncate(text, length = 100) {
                    if (!text) return ''
                    return text.length > length ? text.substring(0, length) + '...' : text
                },

                // Check permissions
                can(permission) {
                    return window.VitalRed.permissions[permission] || false
                },

                // Handle API errors
                handleError(error) {
                    console.error('API Error:', error)

                    let message = 'Ha ocurrido un error inesperado'

                    if (error.response) {
                        if (error.response.status === 422) {
                            // Validation errors
                            const errors = error.response.data.errors
                            if (errors) {
                                message = Object.values(errors).flat().join(', ')
                            }
                        } else if (error.response.data.message) {
                            message = error.response.data.message
                        }
                    } else if (error.message) {
                        message = error.message
                    }

                    this.$showToast(message, 'error')
                },

                // Confirm action
                async confirmAction(title, text, action) {
                    const result = await this.$showConfirm(title, text)
                    if (result.isConfirmed) {
                        try {
                            this.$showLoading()
                            await action()
                            this.$hideLoading()
                        } catch (error) {
                            this.$hideLoading()
                            this.handleError(error)
                        }
                    }
                },

                // Download file
                downloadFile(url, filename) {
                    const link = document.createElement('a')
                    link.href = url
                    link.download = filename
                    document.body.appendChild(link)
                    link.click()
                    document.body.removeChild(link)
                },

                // Copy to clipboard
                async copyToClipboard(text) {
                    try {
                        await navigator.clipboard.writeText(text)
                        this.$showToast('Copiado al portapapeles', 'success')
                    } catch (error) {
                        console.error('Error copying to clipboard:', error)
                        this.$showToast('Error al copiar al portapapeles', 'error')
                    }
                },

                // Debounce function
                debounce(func, wait) {
                    let timeout
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout)
                            func(...args)
                        }
                        clearTimeout(timeout)
                        timeout = setTimeout(later, wait)
                    }
                },

                // Format file size
                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes'
                    const k = 1024
                    const sizes = ['Bytes', 'KB', 'MB', 'GB']
                    const i = Math.floor(Math.log(bytes) / Math.log(k))
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
                },

                // Get time ago
                timeAgo(date) {
                    return moment(date).fromNow()
                },

                // Validate email
                isValidEmail(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                    return re.test(email)
                },

                // Validate phone
                isValidPhone(phone) {
                    const re = /^(\+57|57)?[1-9]\d{9}$/
                    return re.test(phone.replace(/\s/g, ''))
                },

                // Generate random ID
                generateId() {
                    return Math.random().toString(36).substr(2, 9)
                },

                // Scroll to element
                scrollToElement(elementId) {
                    const element = document.getElementById(elementId)
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth' })
                    }
                },

                // Focus element
                focusElement(elementId) {
                    this.$nextTick(() => {
                        const element = document.getElementById(elementId)
                        if (element) {
                            element.focus()
                        }
                    })
                }
            }
        })

        // Global error handler
        app.config.errorHandler = (error, instance, info) => {
            console.error('Vue Error:', error, info)

            // Don't show error toast in development
            if (import.meta.env.PROD) {
                window.showToast('Ha ocurrido un error en la aplicación', 'error')
            }
        }

        // Mount the app
        app.mount(el)
    },
    progress: {
        color: '#2c5aa0',
        showSpinner: true,
    },
})

// Service Worker registration for PWA (optional)
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('SW registered: ', registration)
            })
            .catch((registrationError) => {
                console.log('SW registration failed: ', registrationError)
            })
    })
}

// Handle online/offline status
window.addEventListener('online', () => {
    window.showToast('Conexión restaurada', 'success')
})

window.addEventListener('offline', () => {
    window.showToast('Sin conexión a internet', 'warning')
})

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason)

    if (import.meta.env.PROD) {
        window.showToast('Error de conexión', 'error')
    }

    // Prevent the default browser error handling
    event.preventDefault()
})

// Keyboard shortcuts
document.addEventListener('keydown', (event) => {
    // Ctrl/Cmd + K for search
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault()
        const searchInput = document.querySelector('input[type="search"]')
        if (searchInput) {
            searchInput.focus()
        }
    }

    // Escape to close modals
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show')
        modals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal)
            if (modalInstance) {
                modalInstance.hide()
            }
        })
    }
})

// Auto-save functionality for forms
window.autoSave = {
    timers: new Map(),

    start(formId, saveFunction, delay = 30000) {
        this.stop(formId)

        const timer = setInterval(() => {
            const form = document.getElementById(formId)
            if (form && form.checkValidity()) {
                saveFunction()
            }
        }, delay)

        this.timers.set(formId, timer)
    },

    stop(formId) {
        const timer = this.timers.get(formId)
        if (timer) {
            clearInterval(timer)
            this.timers.delete(formId)
        }
    }
}

// Performance monitoring
if (import.meta.env.PROD) {
    // Monitor page load performance
    window.addEventListener('load', () => {
        setTimeout(() => {
            const perfData = performance.getEntriesByType('navigation')[0]
            if (perfData) {
                console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms')
            }
        }, 0)
    })
}
