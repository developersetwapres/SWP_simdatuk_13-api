@extends('layouts.base')
@section('content')
<table border="0" cellpadding="0" cellspacing="0" class="text_block block-4" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
    <tr>
        <td class="pad" style="padding-bottom:10px;padding-left:45px;padding-right:45px;padding-top:10px;">
            <div style="font-family: sans-serif">
                <div class="" style="font-size: 12px; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; text-align: center; mso-line-height-alt: 18px; color: #393d47; line-height: 1.5;">
                    <p style="margin: 0; text-align: left; mso-line-height-alt: 18px;"><strong><span style="font-size:18px;color:#000000;">Reset Password</span></strong></p>
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
                    <p style="margin: 0; text-align: left; mso-line-height-alt: 21px;"><span style="font-size:14px;color:#000000;">Kami telah menerima permintaan reset password pada akun Anda.</span></p>
                    <p style="margin: 0; text-align: left; mso-line-height-alt: 21px;"><span style="font-size:14px;color:#000000;">Gunakan kode OTP berikut untuk melakukan reset password Anda.</span></p>
                </div>
            </div>
        </td>
    </tr>
</table>
<table border="0" cellpadding="10" cellspacing="0" class="button_block block-5" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
    <tr>
        <td class="pad" style="padding-bottom:20px;padding-left:45px;padding-right:45px;padding-top:20px;">
            <div style="font-family: sans-serif">
                <div class="" style="font-size: 12px; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; text-align: center; mso-line-height-alt: 24px; color: #393d47; line-height: 1.5;">
                    <p style="margin: 0; text-align: center; mso-line-height-alt: 30px;">
                        <span style="font-size:24px; color:#000000; font-weight:bold; letter-spacing:3px; background-color:#f0f0f0; padding:15px 30px; border:2px solid #cccccc; font-family:monospace;">{{$code}}</span>
                    </p>
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
                    <p style="margin: 0; mso-line-height-alt: 21px;"><span style="color:#000000;font-size:14px;">Jika Anda mengalami kendala dengan kode di atas, harap hubungi admin.</span></p>
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
