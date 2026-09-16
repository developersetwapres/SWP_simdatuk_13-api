@extends('layouts.base')
@section('content')
<table border="0" cellpadding="0" cellspacing="0" class="text_block block-4" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
    <tr>
        <td class="pad" style="padding-bottom:10px;padding-left:45px;padding-right:45px;padding-top:10px;">
            <div style="font-family: sans-serif">
                <div class="" style="font-size: 12px; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; text-align: center; mso-line-height-alt: 18px; color: #393d47; line-height: 1.5;">
                    <p style="margin: 0; text-align: left; mso-line-height-alt: 18px;"><strong><span style="font-size:18px;color:#000000;">Verifikasi Email</span></strong></p>
                </div>
            </div>
        </td>
    </tr>
</table>
<table border="0" cellpadding="0" cellspacing="0" class="text_block block-3" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
    <tr>
        <td class="pad" style="padding-left:45px;padding-right:45px;padding-top:10px;">
            <div style="font-family: sans-serif">
                <div class="" style="font-size: 12px; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; mso-line-height-alt: 18px; color: #393d47; line-height: 1.5;">
                    <p style="margin: 0; text-align: left; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;"><span style="">Selamat datang {{$name}}</strong></span></span></p>
                </div>
            </div>
        </td>
    </tr>
</table>
<table border="0" cellpadding="0" cellspacing="0" class="text_block block-4" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
    <tr>
        <td class="pad" style="padding-bottom:10px;padding-left:45px;padding-right:45px;padding-top:10px;">
            <div style="font-family: sans-serif">
                <div class="" style="font-size: 12px; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; text-align: center; mso-line-height-alt: 18px; color: #393d47; line-height: 1.5;">
                    <p style="margin: 0; text-align: left; mso-line-height-alt: 21px;"><span style="font-size:14px;color:#000000;">Anda telah terdaftar sebagai pengguna SIMDATUK (Sistem Informasi Manajemen Data Dukungan Kepegawaian).</span></p>
                    <p style="margin: 0; text-align: left; mso-line-height-alt: 21px;"><span style="font-size:14px;color:#000000;">Gunakan Username dan Password di bawah ini untuk masuk ke website SIMDATUK, setelah itu lakukan update password.</span></p>
                </div>
            </div>
        </td>
    </tr>
</table>
<table border="0" cellpadding="0" cellspacing="0" class="text_block block-6" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
    <tr>
        <td class="pad" style="padding-bottom:10px;padding-left:45px;padding-right:45px;padding-top:10px;">
            <div style="font-family: sans-serif">
                <div class="" style="text-align: left; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; font-size: 12px; mso-line-height-alt: 18px; color: #0068a5; line-height: 1.5;">
                    <p style="margin: 0; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;">Username: {{$username}}</span></p>
                    <p style="margin: 0; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;">Password: {{$password}}</span></p>
                    <p style="margin: 0; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;"></span></p>
                    <p style="margin: 0; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;">Jika Anda mengalami kendala dengan username dan password di atas, harap hubungi admin.</span></p>
                </div>
            </div>
        </td>
    </tr>
</table>
<table border="0" cellpadding="0" cellspacing="0" class="text_block block-6" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
    <tr>
        <td class="pad" style="padding-bottom:10px;padding-left:45px;padding-right:45px;padding-top:10px;">
            <div style="font-family: sans-serif">
                <div class="" style="text-align: left; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; font-size: 12px; mso-line-height-alt: 18px; color: #0068a5; line-height: 1.5;">
                    <p style="margin: 0; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;">Terima kasih,</span></p>
                    <p style="margin: 0; mso-line-height-alt: 18px;"> </p>
                    <p style="margin: 0; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;"><strong>SIMDATUK<strong></span></p>
                </div>
            </div>
        </td>
    </tr>
</table>
@endsection
