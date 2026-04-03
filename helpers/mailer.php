<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; 

function sendMail($toEmail, $subject, $title, $message, $button = []) {
    $mail = new PHPMailer(true);

    try {
        // 1. Read the hidden .env file (Make sure the path to .env is correct for your folder structure)
        $env = parse_ini_file(__DIR__ . '/../.env');
        
        // 2. Assign the key securely
        $google_key = $env['GOOGLE_MAILER_KEY'];

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'shirtifyhubofficial@gmail.com'; 
        
        // 3. Use the variable instead of the hardcoded password
        $mail->Password   = $google_key; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('shirtifyhubofficial@gmail.com', 'Shirtifyhub Official');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        // ══ PREMIUM EMAIL TEMPLATE ══
        $htmlBody = "
        <div style='background-color: #f8fafc; padding: 40px 20px; font-family: \"Inter\", Helvetica, Arial, sans-serif;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
                
                <tr>
                    <td align='center' style='padding: 40px 0 20px 0; background-color: #0f172a;'>
                        <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 4px;'>Shirtifyhub</h1>
                    </td>
                </tr>

                <tr>
                    <td style='padding: 50px 40px;'>
                        <h2 style='color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 20px;'>$title</h2>
                        <div style='color: #475569; font-size: 16px; line-height: 1.8; margin-bottom: 30px;'>
                            $message
                        </div>
        ";

        // Dynamic Action Button
        if (!empty($button)) {
            $htmlBody .= "
                        <div align='center' style='margin-bottom: 20px;'>
                            <a href='{$button['url']}' style='background-color: #0f172a; color: #ffffff; padding: 18px 35px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 14px; display: inline-block; letter-spacing: 1px;'>
                                {$button['text']}
                            </a>
                        </div>";
        }

        $htmlBody .= "
                        <p style='color: #94a3b8; font-size: 14px; margin-top: 40px;'>
                            If you have any questions, simply reply to this email. Our support team is here to help.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style='padding: 0 40px 40px 40px; text-align: center;'>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='border-top: 1px solid #f1f5f9; padding-top: 30px;'>
                            <tr>
                                <td style='color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;'>
                                    Premium Menswear &bull; Curated Quality
                                </td>
                            </tr>
                            <tr>
                                <td style='padding-top: 10px; color: #cbd5e1; font-size: 11px;'>
                                    &copy; " . date('Y') . " Shirtifyhub. Accra, Ghana.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>";

        $mail->Body = $htmlBody;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>