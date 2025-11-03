export default {
  items: [
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
    },
    {
      title: 'Cambiar Contraseña',
      icon: 'mdi-lock-outline',
      to: '/user/update-password',
      roles: ['Administrador', 'Empleado'] // o los roles que quieras permitir
    },
  ],
}
