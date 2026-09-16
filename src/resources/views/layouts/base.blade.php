<!DOCTYPE html>
<html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
    <head>
        <title></title>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
        <meta content="width=device-width, initial-scale=1.0" name="viewport"/><!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch><o:AllowPNG/></o:OfficeDocumentSettings></xml><![endif]-->
        <style>
            * {
                box-sizing: border-box;
            }

            body {
			    margin: 0;
			    padding: 0;
		    }

            a[x-apple-data-detectors] {
                color: inherit !important;
			    text-decoration: inherit !important;
		    }

            #MessageViewBody a {
                color: inherit;
                text-decoration: none;
            }

            p {
                line-height: inherit
            }

            .desktop_hide,
            .desktop_hide table {
                mso-hide: all;
                display: none;
                max-height: 0px;
                overflow: hidden;
            }

            .image_block img+div {
                display: none;
            }

            @media (max-width:670px) {

                .desktop_hide table.icons-inner,
                .social_block.desktop_hide .social-table {
                    display: inline-block !important;
                }

                .icons-inner {
                    text-align: center;
                }

                .icons-inner td {
                    margin: 0 auto;
                }

                .row-content {
                    width: 100% !important;
                }

                .mobile_hide {
				    display: none;
			    }

			    .stack .column {
				    width: 100%;
				    display: block;
		    	}

			    .mobile_hide {
				    min-height: 0;
				    max-height: 0;
				    max-width: 0;
				    overflow: hidden;
				    font-size: 0px;
			    }

			    .desktop_hide,
			    .desktop_hide table {
				    display: table !important;
				    max-height: none !important;
			    }
		    }
	    </style>
    </head>
    <body style="background-color: #000000; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
        <table border="0" cellpadding="0" cellspacing="0" class="nl-container" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #000000;" width="100%">
            <tbody>
                <tr>
                    <td>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-1" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; background-image: url('{{url('assets/bg.png')}}'); background-position: center top; background-repeat: no-repeat; color: #000000; width: 650px;" width="650">
                                            <tbody>
                                                <tr>
                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; padding-top: 45px; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                        @include('layouts.header')
                                                        @yield('content')
                                                        @include('layouts.prefooter')
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- @include('layouts.footer') -->
                    </td>
                </tr>
            </tbody>
        </table><!-- End -->
    </body>
</html>
