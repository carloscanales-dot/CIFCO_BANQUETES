export default {
  items: [
    {
      title: 'Dashboard',
      icon: 'mdi-view-dashboard',
      to: '/dashboard',
      roles: ['Administrador', 'Empleado'],
    },
    {
      title: 'Catálogos',
      icon: 'mdi-folder-outline',
      roles: ['Administrador', 'Empleado'],
      children: [
        {
          title: 'Productos',
          icon: 'mdi-package-variant',
          to: '/ticket/product',
          roles: ['Administrador', 'Empleado'],
        },
        {
          title: 'Estaciones',
          icon: 'mdi-map-marker',
          to: '/ticket/station',
          roles: ['Administrador', 'Empleado'],
        },
        {
          title: 'Locaciones',
          icon: 'mdi-map-marker-outline',
          to: '/ticket/location',
          roles: ['Administrador'],
          exact: true,
        },
      ],
    },
    {
      title: 'Procesos',
      icon: 'mdi-cog-outline',
      roles: ['Administrador', 'Empleado'],
      children: [
        {
          title: 'Ferias',
          icon: 'mdi-calendar-star',
          to: '/ticket/fair',
          roles: ['Administrador', 'Empleado'],
        },
        {
          title: 'Tickets',
          icon: 'mdi-ticket-confirmation',
          to: '/ticket/ticket',
          roles: ['Administrador'],
        },
        {
          title: 'Aperturas y Cierres',
          icon: 'mdi-cash-register',
          to: '/terminal-sessions',
          roles: ['Administrador'],
          exact: true,
        },
      ],
    },
    {
      title: 'Reportes y Consultas',
      icon: 'mdi-chart-box-outline',
      roles: ['Administrador', 'Empleado'],
      children: [
        {
          title: 'Reportes de Ventas',
          icon: 'mdi-file-chart-outline',
          to: '/reportes/ventas',
          roles: ['Administrador', 'Empleado'],
        },
        {
          title: 'Reportes de Empleados',
          icon: 'mdi-account-cash-outline',
          to: '/reportes/empleados',
          roles: ['Administrador'],
        },
        {
          title: 'Transacciones de Ventas',
          icon: 'mdi-receipt-text-outline',
          to: '/transacciones/ventas',
          roles: ['Administrador', 'Empleado'],
        },
        {
          title: 'Transacciones de Empleados',
          icon: 'mdi-account-cash',
          to: '/transacciones/empleados',
          roles: ['Administrador'],
        },
      ],
    },
    {
      title: 'Administración',
      icon: 'mdi-cog-transfer-outline',
      roles: ['Administrador'],
      children: [
        {
          title: 'Usuarios',
          icon: 'mdi-account-multiple',
          to: '/admin/users?page=1',
          roles: ['Administrador'],
        },
        {
          title: 'Empleados',
          icon: 'mdi-account-tie',
          to: '/admin/employees',
          roles: ['Administrador'],
        },
        {
          title: 'Payment Terminals',
          icon: 'mdi-credit-card-outline',
          to: '/payment-terminals',
          roles: ['Administrador'],
        },
        {
          title: 'Impresoras',
          icon: 'mdi-printer-outline',
          to: '/admin/printers',
          roles: ['Administrador'],
        },
        {
          title: 'Historial de Reimpresiones',
          icon: 'mdi-printer-check',
          to: '/admin/reprint-logs',
          roles: ['Administrador'],
        },
      ],
    },
    {
      title: 'Mi Cuenta',
      icon: 'mdi-account-circle-outline',
      roles: ['Administrador', 'Empleado'],
      children: [
        {
          title: 'Cambiar Contraseña',
          icon: 'mdi-lock-outline',
          to: '/user/update-password',
          roles: ['Administrador', 'Empleado'],
        },
      ],
    },
  ],
}
