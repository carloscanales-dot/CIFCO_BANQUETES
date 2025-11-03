<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tu nueva contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background-color: white; border-radius: 8px; padding: 20px;">
        <tr>
            <td style="text-align: center;">
                <h2 style="color: #000;">Hola {{ $user->name }},</h2>
                <p style="color: #333;">Tu contraseña ha sido reseteada correctamente.</p>
                <p style="color: #333;">Tu nueva contraseña temporal es:</p>
                <h3 style="background-color: #000; color: #fff; display: inline-block; padding: 10px 20px; border-radius: 6px;">
                    {{ $newPassword }}
                </h3>
                <p style="color: #333;">Te recomendamos iniciar sesión y cambiarla por una más segura.</p>
                <br>
                <small style="color: #777;">Este correo fue generado automáticamente. No respondas a este mensaje.</small>
            </td>
        </tr>
    </table>
</body>
</html>
