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
      title: 'Usuarios',
      icon: 'mdi-account-multiple',
      to: '/admin/users?page=1',
      roles: ['Administrador'],
    },
    {
      title: 'Cambiar Contraseña',
      icon: 'mdi-lock-outline',
      to: '/user/update-password',
      roles: ['Administrador', 'Empleado'] // o los roles que quieras permitir
    }
  ],
}
