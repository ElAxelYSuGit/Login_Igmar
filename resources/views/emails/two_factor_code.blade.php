<!DOCTYPE html>
<html>
<head>
    <title>Código de Verificación</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2>Hola,</h2>
    <p>Has intentado iniciar sesión. Para continuar, por favor ingresa el siguiente código de seguridad:</p>
    
    <h1 style="background-color: #f3f4f6; padding: 15px; text-align: center; letter-spacing: 5px; color: #333;">
        {{ $code }}
    </h1>
    
    <p>Este código expirará en 10 minutos. Si no intentaste iniciar sesión, ignora este correo.</p>
</body>
</html>