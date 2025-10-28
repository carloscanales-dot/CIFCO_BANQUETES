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
  ],
}
