export default {
  items: [
    {
      title: 'Lector QR',
      icon: 'mdi-qrcode-scan',
      to: '/ticket/reader/index',
      roles: ['Administrador', 'Empleado', 'Cajero'],
    },
    {
      title: 'Ticket por estacion',
      icon: 'mdi-ticket',
      to: '/ticket/stationTicket',
      roles: ['Administrador', 'Empleado', 'Cajero'],
    },
  ],
}
