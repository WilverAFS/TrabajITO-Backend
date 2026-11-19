<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrabajITO</title>
</head>

<body style="margin:0;padding:30px 12px;background:#e9f1f4;font-family:Arial,Helvetica,sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                   style="max-width:580px;background:#14202a;border-radius:16px;overflow:hidden;">

                <tr>
                    <td style="background:#075c63;padding:30px 35px;">
                        <div style="font-size:30px;font-weight:bold;color:#ffffff;">
                            Trabaj<span style="color:#a4f4c5;">ITO</span>
                        </div>
                        <div style="color:#ccebe8;font-size:13px;margin-top:6px;">
                            Conectamos personas y oportunidades
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:42px 35px;color:#edf5f7;">

                        @if($esSeguridad)
                            <div style="color:#a4f4c5;font-size:13px;font-weight:bold;">
                                SEGURIDAD DE TU CUENTA
                            </div>

                            <h1 style="font-size:29px;line-height:1.3;margin:16px 0 25px;color:#ffffff;">
                                Tu contraseña fue actualizada
                            </h1>

                            <p style="font-size:17px;line-height:1.7;">
                                Hola, {{ $nombre }}:
                            </p>

                            <p style="font-size:16px;line-height:1.8;color:#cbd8dd;">
                                Te informamos que la contraseña de tu cuenta
                                de TrabajITO fue modificada correctamente.
                            </p>

                            <p style="font-size:16px;line-height:1.8;color:#cbd8dd;">
                                Si realizaste este cambio, no necesitas hacer nada.
                                Si no reconoces esta actividad, recupera
                                el acceso a tu cuenta y contacta a soporte.
                            </p>
                        @else
                            <div style="color:#a4f4c5;font-size:13px;font-weight:bold;">
                                TU AVENTURA COMIENZA AQUÍ
                            </div>

                            <h1 style="font-size:30px;line-height:1.3;margin:16px 0 25px;color:#ffffff;">
                                ¡Bienvenido a TrabajITO!
                            </h1>

                            <p style="font-size:17px;line-height:1.7;">
                                ¡Hola, {{ $nombre }}!
                            </p>

                            <p style="font-size:16px;line-height:1.8;color:#cbd8dd;">
                                Tu cuenta se registró correctamente.
                                Nos alegra que formes parte de nuestra comunidad.
                            </p>

                            <p style="font-size:16px;line-height:1.8;color:#cbd8dd;">
                                En TrabajITO queremos facilitar la conexión
                                entre personas que buscan servicios y
                                profesionales que ofrecen su trabajo.
                            </p>
                        @endif

                        <p style="margin-top:32px;font-size:15px;color:#a4f4c5;">
                            Equipo TrabajITO
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background:#0c171f;padding:25px 35px;text-align:center;">
                        <p style="color:#a6b7bd;font-size:12px;line-height:1.7;margin:0;">
                            Este es un mensaje automático de TrabajITO.
                            <br>
                            Por favor, no respondas a este correo.
                        </p>
                        <p style="color:#718a91;font-size:12px;margin:14px 0 0;">
                            © TrabajITO
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
