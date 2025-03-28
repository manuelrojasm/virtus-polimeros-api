<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public $fromEmail = 'santipurdy14@gmail.com';  // Tu correo de envío
    public $fromName = 'plasTIC';     // El nombre que aparecerá como remitente

    public $SMTPHost = 'smtp.gmail.com';         // El servidor SMTP
    public $SMTPUser = 'santipurdy14@gmail.com';   // El correo del usuario
    public $SMTPPass = 'btda dqjv xpoo wijv';      // La contraseña del correo
    public $SMTPPort = 587;  
    public $SMTPCrypto = 'tls';                      
    public $SMTPTimeout = 30;

    // Opciones adicionales
    public $mailType = 'html'; // Tipo de correo (puede ser 'html' o 'text')
    public $charset = 'UTF-8';
    public $wordWrap = true;
    public $logging = true;  
}