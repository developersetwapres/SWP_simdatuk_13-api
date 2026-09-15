<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <style type="text/css">
        html * {
            font-family: Inter !important;
            color: #394346;
        }

        table {
            border-collapse: collapse
        }

        @page {
            margin: 72px 32px;
        }

        header {
            position: fixed;
            top: -42px;
            left: 0px;
            right: 0px;
        }

        p {
            page-break-after: always;
        }

        p:last-child {
            page-break-after: never;
        }

        .logo {
            width: 200px;
        }

        .page_break {
            page-break-before: always;
        }

        .profile-image {
            width: 120px;
            height: 160px;
        }

        .title {
            font-size: 15px;
            font-weight: 700;
        }

        .name {
            font-size: 14px;
            font-weight: 700;
        }

        .grade {
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }

        .table-section-1-title {
            font-size: 10px;
            font-weight: 400;
            word-wrap: break-word;
        }

        .table-section-1-body {
            font-size: 10px;
            font-weight: 600;
            word-wrap: break-word;
        }

        .title-profile {
            font-size: 12px;
            font-weight: 700;
            margin-top: 12px;
        }

        .table-section-2-title {
            min-width: 200px;
            font-size: 10px;
            font-weight: 400;
            width: 0.1%;
            white-space: nowrap;
            padding-bottom: 4px;
            padding-top: 6px;
            word-wrap: break-word;
        }

        .table-section-2-body {
            font-size: 10px;
            font-weight: 500;
            text-align: left;
            padding-bottom: 4px;
            padding-top: 6px;
            word-wrap: break-word;
        }

        .table-section-3 {
            table-layout: fixed;
            width: 100%;
            margin-top: 8px;
        }

        .table-section-3-title {
            font-size: 10px;
            font-weight: 700;
            padding: 4px;
            color: white;
            word-wrap: break-word;
        }

        .table-section-3-title-row {
            background-color: #394346;
        }

        .table-section-3-body {
            font-size: 10px;
            font-weight: 400;
            text-align: left;
            padding-bottom: 4px;
            padding-top: 4px;
            padding-left: 4px;
            word-wrap: break-word;
        }

        a {
            color: blue !important;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <!-- start of page 1 -->
    <header>
        <img src='{{ \App\Support\ExportLogo::dataUri() }}' class="logo" />
    </header>

    <center>
        <div class="title">
            Daftar Riwayat Hidup
        </div>
    </center>

    <table>
        <tr>
            <td>
                <img src="{{ $photoProfile}}" class="profile-image" />
            </td>
            <td>
                <table style="margin-left: 12px;">
                    <tr>
                        <td class="name">
                            {{ $userName }}
                        </td>
                    </tr>
                    <tr>
                        <td class="grade">
                            {{ $currentPosition }}
                        </td>
                    </tr>
                    <tr>
                        <table style="width: 100%; margin-top: 4px;">
                            <tr>
                                @if($userType == 3)
                                <td class="table-section-1-title">
                                    NIP
                                </td>
                                @else
                                <td class="table-section-1-title">
                                    Eselon
                                </td>
                                <td class="table-section-1-title">
                                    Golongan
                                </td>
                                <td class="table-section-1-title">
                                    NIP/NRP
                                </td>
                                @endif
                            </tr>
                            <tr>
                                @if($userType == 3)
                                <td class="table-section-1-body">
                                    {{ $userNIP }}
                                </td>
                                @else
                                <td class="table-section-1-body">
                                    {{ $userEchelons }}
                                </td>
                                <td class="table-section-1-body">
                                    {{ $userCurrentGrade }}
                                </td>
                                <td class="table-section-1-body">
                                    {{ $userNIP }}
                                </td>
                                @endif
                            </tr>
                        </table>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="title-profile">Data Pribadi</div>

    <table style="width: 100%; margin-top: 8px;">
        @foreach($userProfile as $key => $value)
        <tr style="border-bottom: 1px solid #F0F0F0;">
            <td class="table-section-2-title">{{ $key }}:</td>
            <td class="table-section-2-body">{{ $value }}</td>
        </tr>
        @endforeach
    </table>

    <!-- <div class="page_break"></div> -->

    <!-- end of page 1 -->
    @if(isset($userCollege))
    <div class="title-profile page_break">Riwayat Pendidikan</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Tingkat</td>
            <td class="table-section-3-title">Nama Sekolah</td>
            <td class="table-section-3-title">Wilayah</td>
            <td class="table-section-3-title">Akreditasi</td>
            <td class="table-section-3-title">Fakultas</td>
            <td class="table-section-3-title">Jurusan</td>
            <td class="table-section-3-title">Tahun Lulus</td>
            <td class="table-section-3-title">Ijasah</td>
            <td class="table-section-3-title">Surat Keterangan Tugas Belajar</td>
            <td class="table-section-3-title">Surat Keputusan Pencantuman Gelar</td>
        </thead>

        <tbody>
            @php $indexCollege=1 @endphp
            @foreach($userCollege as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexCollege++ }}</td>
                <td class="table-section-3-body">
                    @switch($value->level)
                    @case(1)
                    SD/Sederajat
                    @break
                    @case(2)
                    SLTP/Sederajat
                    @break
                    @case(3)
                    SLTA/Sederajat
                    @break
                    @case(4)
                    Diploma I/II
                    @break
                    @case(5)
                    Akademik/D3/S.Muda
                    @break
                    @case(6)
                    Diploma IV/Strata I
                    @break
                    @case(7)
                    Strata II
                    @break
                    @case(8)
                    Strata III
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->name }}</td>
                <td class="table-section-3-body">
                    @switch($value->study_area)
                    @case(1)
                    Dalam Negeri
                    @break
                    @case(2)
                    Luar Negeri
                    @break
                    @default
                    -
                    @endswitch
                </td>
                <td class="table-section-3-body">{{ $value->accreditation }}</td>
                <td class="table-section-3-body">{{ $value->faculty }}</td>
                <td class="table-section-3-body">{{ $value->major }}</td>
                <td class="table-section-3-body">{{ $value->year_of_graduation }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->degree_document))
                    <a href="{{ $value->degree_document }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
                <td class="table-section-3-body">
                    @if(!is_null($value->study_assignment_letter))
                    <a href="{{ $value->study_assignment_letter }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
                <td class="table-section-3-body">
                    @if(!is_null($value->academic_title_letter))
                    <a href="{{ $value->academic_title_letter }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userPosition) && isset($permissions[11]) && $permissions[11] == 1)
    @if(!isset($userCollege))
    <div class="title-profile page_break">Riwayat Jabatan</div>
    @else
    <div class="title-profile">Riwayat Jabatan</div>
    @endif
    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Jabatan</td>
            <td class="table-section-3-title">Jenjang Jabatan</td>
            <td class="table-section-3-title">Keterangan Jabatan</td>
            <td class="table-section-3-title">TMT Jabatan</td>
            <td class="table-section-3-title">SK Menjabat</td>
            <td class="table-section-3-title">SK Jabatan</td>
            <td class="table-section-3-title">No SK Jabatan</td>
            <td class="table-section-3-title">Tanggal SK Jabatan</td>
        </thead>

        <tbody>
            @php $indexPosition=1 @endphp
            @foreach($userPosition as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexPosition++ }}</td>
                <td class="table-section-3-body">{{ $value->position }}</td>
                <td class="table-section-3-body">{{ $value->echelon_name }}</td>
                <td class="table-section-3-body">
                    @switch($value->position_status)
                    @case(1)
                    Promosi
                    @break
                    @case(2)
                    Mutasi
                    @break
                    @case(3)
                    Impassing
                    @break
                    @case(4)
                    Konversi
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->effective_date }}</td>
                <td class="table-section-3-body">{{ $value->decree }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->decree_document))
                    <a href="{{ $value->decree_document }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
                <td class="table-section-3-body">{{ $value->decree_number }}</td>
                <td class="table-section-3-body">{{ $value->decree_date }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userGrade) && isset($permissions[12]) && $permissions[12] == 1)
    <div class="title-profile">Riwayat Golongan</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Golongan</td>
            <td class="table-section-3-title">TMT Golongan</td>
            <td class="table-section-3-title">SK Golongan</td>
            <td class="table-section-3-title">SK Golongan</td>
            <td class="table-section-3-title">Jenis SK Golongan</td>
            <td class="table-section-3-title">No SK Golongan</td>
            <td class="table-section-3-title">Tanggal SK Golongan</td>
            <td class="table-section-3-title">Keterangan Golongan</td>
            <td class="table-section-3-title">Status Golongan</td>
        </thead>

        <tbody>
            @php $indexGrade=1 @endphp
            @foreach($userGrade as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexGrade++ }}</td>
                <td class="table-section-3-body">{{ $value->grade_name }} ({{ $value->grade_code }})</td>
                <td class="table-section-3-body">{{ $value->effective_date }}</td>
                <td class="table-section-3-body">{{ $value->decree_name }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->decree_document))
                    <a href="{{ $value->decree_document }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
                <td class="table-section-3-body">{{ $value->type_of_decree_name }}</td>
                <td class="table-section-3-body">{{ $value->decree_number }}</td>
                <td class="table-section-3-body">{{ $value->decree_date }}</td>
                <td class="table-section-3-body">{{ $value->description }}</td>
                <td class="table-section-3-body">{{ $value->status === 1 ? 'Aktif' : 'Tidak Aktif' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userTrainingStructural) && isset($permissions[13]) && $permissions[13] == 1)
    <div class="title-profile">Riwayat Pelatihan Struktural</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Nama Diklat</td>
            <td class="table-section-3-title">Jenjang</td>
            <td class="table-section-3-title">Tanggal Pelaksanaan</td>
            <td class="table-section-3-title">Penyelenggara</td>
            <td class="table-section-3-title">Jam Pelajaran</td>
            <td class="table-section-3-title">Sertifikat</td>
        </thead>

        <tbody>
            @php $indexTrainingStructural=1 @endphp
            @foreach($userTrainingStructural as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexTrainingStructural++ }}</td>
                <td class="table-section-3-body">{{ $value->name }}</td>
                <td class="table-section-3-body">{{ $value->level }}</td>
                <td class="table-section-3-body">{{ $value->start_date.' - '.$value->end_date }}</td>
                <td class="table-section-3-body">{{ $value->organizer }}</td>
                <td class="table-section-3-body">{{ $value->duration }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->certificate))
                    <a href="{{ $value->certificate }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userTrainingFunctional) && isset($permissions[14]) && $permissions[14] == 1)
    <div class="title-profile">Riwayat Pelatihan Fungsional</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Nama Diklat</td>
            <td class="table-section-3-title">Tanggal Pelaksanaan</td>
            <td class="table-section-3-title">Penyelenggara</td>
            <td class="table-section-3-title">Jam Pelajaran</td>
            <td class="table-section-3-title">Sertifikat</td>
        </thead>

        <tbody>
            @php $indexTrainingFunctional=1 @endphp
            @foreach($userTrainingFunctional as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexTrainingFunctional++ }}</td>
                <td class="table-section-3-body">{{ $value->name }}</td>
                <td class="table-section-3-body">{{ $value->start_date.' - '.$value->end_date }}</td>
                <td class="table-section-3-body">{{ $value->organizer }}</td>
                <td class="table-section-3-body">{{ $value->duration }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->certificate))
                    <a href="{{ $value->certificate }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userTrainingTechnical) && isset($permissions[15]) && $permissions[15] == 1)
    <div class="title-profile">Riwayat Pelatihan Teknis</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Nama Diklat</td>
            <td class="table-section-3-title">Tanggal Pelaksanaan</td>
            <td class="table-section-3-title">Penyelenggara</td>
            <td class="table-section-3-title">Jam Pelajaran</td>
            <td class="table-section-3-title">Sertifikat</td>
        </thead>

        <tbody>
            @php $indexTrainingTechnical=1 @endphp
            @foreach($userTrainingTechnical as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexTrainingTechnical++ }}</td>
                <td class="table-section-3-body">{{ $value->name }}</td>
                <td class="table-section-3-body">{{ $value->start_date.' - '.$value->end_date }}</td>
                <td class="table-section-3-body">{{ $value->organizer }}</td>
                <td class="table-section-3-body">{{ $value->duration }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->certificate))
                    <a href="{{ $value->certificate }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userAward) && isset($permissions[16]) && $permissions[16] == 1)
    <div class="title-profile">Riwayat Penghargaan</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Nama Penghargaan</td>
            <td class="table-section-3-title">Tahun SK</td>
            <td class="table-section-3-title">Instansi Pemberi Penghargaan</td>
        </thead>

        <tbody>
            @php $indexAward=1 @endphp
            @foreach($userAward as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexAward++ }}</td>
                <td class="table-section-3-body">{{ $value->recognition_name }}</td>
                <td class="table-section-3-body">{{ $value->decree_year }}</td>
                <td class="table-section-3-body">{{ $value->awarding_institution }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userSKP) && isset($permissions[17]) && $permissions[17] == 1)
    <div class="title-profile">Riwayat SKP</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Periode Penilaian</td>
            <td class="table-section-3-title">Tahun</td>
            <td class="table-section-3-title">Rating Perilaku Kerja</td>
            <td class="table-section-3-title">Predikat Kinerja Pegawai</td>
            <td class="table-section-3-title">Capaian Kinerja Organisasi</td>
        </thead>

        <tbody>
            @php $indexSKP=1 @endphp
            @foreach($userSKP as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexSKP++ }}</td>
                <td class="table-section-3-body">{{ $value->appraisal_period }}</td>
                <td class="table-section-3-body">{{ $value->year }}</td>
                <td class="table-section-3-body">
                    @switch($value->work_behavior_rating)
                    @case(1)
                    Diatas Ekspektasi
                    @break
                    @case(2)
                    Sesuai Ekspektasi
                    @break
                    @case(3)
                    Dibawah Ekspektasi
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">
                    @switch($value->employee_performance_predicate)
                    @case(1)
                    Sangat Baik
                    @break
                    @case(2)
                    Baik
                    @break
                    @case(3)
                    Butuh Perbaikan
                    @break
                    @case(4)
                    Kurang
                    @break
                    @case(5)
                    Sangat Kurang
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">
                    @switch($value->organizational_performance_achievement)
                    @case(1)
                    Sangat Baik
                    @break
                    @case(2)
                    Baik
                    @break
                    @case(3)
                    Cukup
                    @break
                    @default
                    -
                    @endswitch</td>

            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userCredit))
    <div class="title-profile">Riwayat Penetapan Angka Kredit Terakhir</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Jabatan</td>
            <td class="table-section-3-title">Periode</td>
            <td class="table-section-3-title">Tahun</td>
            <td class="table-section-3-title">Bulan</td>
            <td class="table-section-3-title">Angka Kredit Terakhir</td>
        </thead>

        <tbody>
            @php $indexScore=1 @endphp
            @foreach($userCredit as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexScore++ }}</td>
                <td class="table-section-3-body">{{ $value->position }}</td>
                <td class="table-section-3-body">
                    @switch($value->period)
                    @case(1)
                    Triwulan 1
                    @break
                    @case(2)
                    Triwulan 2
                    @break
                    @case(3)
                    Triwulan 3
                    @break
                    @case(4)
                    Triwulan 4
                    @break
                    @case(5)
                    Tahunan
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->year }}</td>
                <td class="table-section-3-body">{{ $value->start_month_name . ' - '. $value->end_month_name }}</td>
                <td class="table-section-3-body">{{ $value->score }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userPerformance) && isset($permissions[18]) && $permissions[18] == 1)
    <div class="title-profile">Riwayat Penilaian Prestasi Kerja</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Periode PPK</td>
            <td class="table-section-3-title">Nilai Prestasi Kerja</td>
            <td class="table-section-3-title">Keterangan</td>
        </thead>

        <tbody>
            @php $indexPerformance=1 @endphp
            @foreach($userPerformance as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexPerformance++ }}</td>
                <td class="table-section-3-body">{{ $value->performance_period }}</td>
                <td class="table-section-3-body">{{ $value->work_performance_score }}</td>
                <td class="table-section-3-body">
                    @switch($value->description)
                    @case(1)
                    Kurang
                    @break
                    @case(2)
                    Sedang
                    @break
                    @case(3)
                    Cukup
                    @break
                    @case(4)
                    Baik
                    @break
                    @case(5)
                    Sangat Baik
                    @break
                    @default
                    -
                    @endswitch</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userPunishment) && isset($permissions[19]) && $permissions[19] == 1)
    <div class="title-profile">Riwayat Hukuman Disiplin</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Jenis Hukuman</td>
            <td class="table-section-3-title">Tingkat Hukuman</td>
            <td class="table-section-3-title">No SK Hukuman Disiplin</td>
            <td class="table-section-3-title">Tanggal SK Hukuman Disiplin</td>
            <td class="table-section-3-title">Tanggal Hukuman Disiplin</td>
        </thead>

        <tbody>
            @php $indexPunishment=1 @endphp
            @foreach($userPunishment as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexPunishment++ }}</td>
                <td class="table-section-3-body">{{ $value->disciplinary_name }}</td>
                <td class="table-section-3-body">{{ $value->disciplinary_description }}</td>
                <td class="table-section-3-body">{{ $value->decree_number }}</td>
                <td class="table-section-3-body">{{ $value->date_of_decree }}</td>
                <td class="table-section-3-body">{{ $value->start_date }} - {{ $value->end_date }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userFamily))
    <div class="title-profile">Keluarga</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Nama Anggota Keluarga</td>
            <td class="table-section-3-title">Jenis Kelamin</td>
            <td class="table-section-3-title">Tempat Lahir</td>
            <td class="table-section-3-title">Tanggal Lahir</td>
            <td class="table-section-3-title">Hubungan Keluarga</td>
        </thead>

        <tbody>
            @php $indexFamily=1 @endphp
            @foreach($userFamily as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexFamily++ }}</td>
                <td class="table-section-3-body">{{ $value->name }}</td>
                <td class="table-section-3-body">
                    @switch($value->gender)
                    @case(0)
                    Perempuan
                    @break
                    @case(1)
                    Laki-Laki
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->place_of_birth }}</td>
                <td class="table-section-3-body">{{ $value->date_of_birth }}</td>
                <td class="table-section-3-body">
                    @switch($value->relationship_status)
                    @case(1)
                    Kepala Keluarga
                    @break
                    @case(2)
                    Suami
                    @break
                    @case(3)
                    Istri
                    @break
                    @case(4)
                    Anak
                    @break
                    @case(5)
                    Menantu
                    @break
                    @case(6)
                    Cucu
                    @break
                    @case(7)
                    Orang Tua
                    @break
                    @case(8)
                    Mertua
                    @break
                    @case(9)
                    Family Lainnya
                    @break
                    @default
                    -
                    @endswitch</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userLeave))
    <div class="title-profile">Cuti</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Periode</td>
            <td class="table-section-3-title">Jenis Cuti</td>
            <td class="table-section-3-title">No Cuti</td>
            <td class="table-section-3-title">Keterangan</td>
            <td class="table-section-3-title">Surat Cuti</td>
        </thead>

        <tbody>
            @php $indexLeave=1 @endphp
            @foreach($userLeave as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexLeave++ }}</td>
                <td class="table-section-3-body">{{ $value->start_date }} - {{ $value->end_date }}</td>
                <td class="table-section-3-body">
                    @switch($value->type)
                    @case(1)
                    Cuti diluar Tanggungan Negara
                    @break
                    @case(2)
                    Cuti Sakit
                    @break
                    @case(3)
                    Cuti Besar
                    @break
                    @case(4)
                    Cuti Bersalin
                    @break
                    @case(5)
                    Cuti Belajar Luar Negeri
                    @break
                    @case(6)
                    Cuti Tahunan Luar Negeri
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->number }}</td>
                <td class="table-section-3-body">{{ $value->description }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->letter))
                    <a href="{{ $value->letter }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userNotes) && isset($permissions[27]) && $permissions[27] == 1)
    <div class="title-profile">Catatan</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Tanggal</td>
            <td class="table-section-3-title">Inputer</td>
            <td class="table-section-3-title">Catatan</td>
        </thead>

        <tbody>
            @php $indexNotes=1 @endphp
            @foreach($userNotes as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexNotes++ }}</td>
                <td class="table-section-3-body">{{ $value->created_at }}</td>
                <td class="table-section-3-body">{{ $value->giver_name }}</td>
                <td class="table-section-3-body">{{ $value->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userAssessment))
    <div class="title-profile">Hasil Assessment</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Tanggal</td>
            <td class="table-section-3-title">Hasil</td>
            <td class="table-section-3-title">Penyelenggara</td>
            <td class="table-section-3-title">File Pendukung</td>
        </thead>

        <tbody>
            @php $indexAssessment=1 @endphp
            @foreach($userAssessment as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexAssessment++ }}</td>
                <td class="table-section-3-body">{{ $value->event_date }}</td>
                <td class="table-section-3-body">
                    @switch($value->point)
                    @case(1)
                    Kurang
                    @break
                    @case(2)
                    Sedang
                    @break
                    @case(3)
                    Cukup
                    @break
                    @case(4)
                    Baik
                    @break
                    @case(5)
                    Sangat Baik
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->organizer }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->assessment_document))
                    <a href="{{ $value->assessment_document }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userAssessmentCompetency))
    <div class="title-profile">Hasil Uji Kompetensi</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Tanggal</td>
            <td class="table-section-3-title">Hasil</td>
            <td class="table-section-3-title">Penyelenggara</td>
            <td class="table-section-3-title">File Pendukung</td>
        </thead>

        <tbody>
            @php $indexCompetency=1 @endphp
            @foreach($userAssessmentCompetency as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexCompetency++ }}</td>
                <td class="table-section-3-body">{{ $value->event_date }}</td>
                <td class="table-section-3-body">
                    @switch($value->point)
                    @case(1)
                    Kurang
                    @break
                    @case(2)
                    Sedang
                    @break
                    @case(3)
                    Cukup
                    @break
                    @case(4)
                    Baik
                    @break
                    @case(5)
                    Sangat Baik
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->organizer }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->competency_document))
                    <a href="{{ $value->competency_document }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($userAssessmentTalent) && isset($permissions[28]) && $permissions[28] == 1)
    <div class="title-profile">Hasil Talent Pool</div>

    <table class="table-section-3">
        <thead class="table-section-3-title-row">
            <td class="table-section-3-title">No</td>
            <td class="table-section-3-title">Tanggal</td>
            <td class="table-section-3-title">Hasil</td>
            <td class="table-section-3-title">Penyelenggara</td>
            <td class="table-section-3-title">File Pendukung</td>
        </thead>

        <tbody>
            @php $indexTalent=1 @endphp
            @foreach($userAssessmentTalent as $value)
            <tr style="border-bottom: 1px solid #F0F0F0;">
                <td class="table-section-3-body">{{ $indexTalent++ }}</td>
                <td class="table-section-3-body">{{ $value->event_date }}</td>
                <td class="table-section-3-body">
                    @switch($value->point)
                    @case(1)
                    Kurang
                    @break
                    @case(2)
                    Sedang
                    @break
                    @case(3)
                    Cukup
                    @break
                    @case(4)
                    Baik
                    @break
                    @case(5)
                    Sangat Baik
                    @break
                    @default
                    -
                    @endswitch</td>
                <td class="table-section-3-body">{{ $value->organizer }}</td>
                <td class="table-section-3-body">
                    @if(!is_null($value->talent_document))
                    <a href="{{ $value->talent_document }}">Lihat File</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>

</html>
