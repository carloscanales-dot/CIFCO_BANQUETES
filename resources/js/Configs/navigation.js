export default {
  items: [
    {
      title: 'Dashboard',
      icon: 'mdi-view-dashboard',
      to: '/dashboard',
      roles: ['Administrador', 'Empleado'],
    },
    {
      title: 'Ferias',
      icon: 'mdi-calendar-star',
      to: '/ticket/fair',
      roles: ['Administrador', 'Empleado'],
    },
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
      title: 'Tickets',
      icon: 'mdi-ticket-confirmation',
      to: '/ticket/ticket',
      roles: ['Administrador'],
    },
    {
      title: 'Lector QR',
      icon: 'mdi-qrcode-scan',
      to: '/ticket/reader/index',
      roles: ['Administrador', 'Empleado', 'Lector'],
    },
    {
      title: 'Ticket por estacion',
      icon: 'mdi-ticket',
      to: '/ticket/stationTicket',
      roles: ['Administrador', 'Empleado'],
    }
  ],
}
