    <style>
        html * {
            font-family: Inter !important;
            color: #394346;
        }

        .logo {
            width: 200px;
        }

        header {
            position: fixed !important;
            top: -42px;
            left: 0px;
            right: 0px;
        }

        @page {
            margin: 72px 32px;
        }

        body {
            /*margin: 20px;*/
        }

        .container {
            /*max-width: 90%;*/
            /*margin: auto;*/
        }

        .title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
            font-weight: 400;
        }

        ol {
            padding-left: 20px;
        }

        th,
        td {
            /*padding: 8px;*/
            text-align: left;
            font-size: 10px;
            font-weight: 400;
            vertical-align: top;
        }

        th {
            font-size: 10px;
            font-weight: 400;
        }

        .right {
            text-align: right;
        }

        .section-table td {
            /*padding: 4px 8px;*/
        }

        .section-table {
            margin-bottom: 10px;
        }

        .section-header-color {
            padding: 8px;
            background-color: #394346;
            color: white;
            word-wrap: break-word;
            font-size: 10px;
            font-weight: 400;
        }

        html * {
            font-family: Inter !important;
            color: #394346;
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
            /*page-break-after: never;*/
        }

        .page-break {
            page-break-after: always;
        }

        .logo {
            width: 200px;
        }

        .title {
            font-size: 15px;
            font-weight: 700;
        }
    </style>

    <table class="section-table" style="border: none">
        <thead>
            <tr>
                <th colspan="11"></th>
            </tr>
            <tr bgcolor="#394346" style="color: #394346">
                <th class="section-header-color">No</th>
                @if ($toggleField['isName'])
                    <th class="section-header-color">Nama</th>
                @endif
                @if ($toggleField['isTitlePrefix'])
                    <th class="section-header-color">Gelar Depan</th>
                @endif
                @if ($toggleField['isTitleSuffix'])
                    <th class="section-header-color">Gelar Belakang</th>
                @endif
                @if ($toggleField['isNameWithTitle'])
                    <th class="section-header-color">Nama Beserta Gelar</th>
                @endif
                @if ($toggleField['isNip'])
                    <th class="section-header-color">NIP/NRP</th>
                @endif
                @if ($toggleField['isBirthPlaceDate'])
                    <th class="section-header-color">Tempat, Tanggal Lahir</th>
                @endif
                @if ($toggleField['isAge'])
                    <th class="section-header-color">Umur</th>
                @endif
                @if ($toggleField['isReligion'])
                    <th class="section-header-color">Agama</th>
                @endif
                @if ($toggleField['isGender'])
                    <th class="section-header-color">Jenis Kelamin</th>
                @endif
                @if ($toggleField['isMaritalStatus'])
                    <th class="section-header-color">Status Perkawinan</th>
                @endif
                @if ($toggleField['isMarriageDate'])
                    <th class="section-header-color">Tanggal Perkawinan</th>
                @endif
                @if ($toggleField['isMarriageDescription'])
                    <th class="section-header-color">Keterangan Perkawinan</th>
                @endif
                @if ($toggleField['isEmployeeType'])
                    <th class="section-header-color">Jenis Pegawai</th>
                @endif
                @if ($toggleField['isAssistanceType'])
                    <th class="section-header-color">Jenis Perbantuan</th>
                @endif
                @if ($toggleField['isOutsourcingType'])
                    <th class="section-header-color">Jenis Outsourcing</th>
                @endif
                @if ($toggleField['isDateCPNS'])
                    <th class="section-header-color">TMT CPNS</th>
                @endif
                @if ($toggleField['isDatePNS'])
                    <th class="section-header-color">TMT PNS</th>
                @endif
                @if ($toggleField['isStartDate'])
                    <th class="section-header-color">Tanggal Mulai Bekerja</th>
                @endif
                @if ($toggleField['isEndDate'])
                    <th class="section-header-color">Tanggal Terakhir Bekerja</th>
                @endif
                @if ($toggleField['isWorkDuration'])
                    <th class="section-header-color">Masa Kerja Keseluruhan</th>
                @endif
                @if ($toggleField['isGradeDuration'])
                    <th class="section-header-color">Masa Kerja Golongan</th>
                @endif
                @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                    <th class="section-header-color">Eselon I</th>
                @endif
                @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                    <th class="section-header-color">Eselon II</th>
                @endif
                @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                    <th class="section-header-color">Eselon III</th>
                @endif
                @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                    <th class="section-header-color">Eselon IV</th>
                @endif
                @if ($toggleField['isPosition'])
                    <th class="section-header-color">Jabatan</th>
                @endif
                @if ($toggleField['isFullPosition'])
                    <th class="section-header-color">Jabatan Lengkap</th>
                @endif
                @if ($toggleField['isDatePosition'])
                    <th class="section-header-color">TMT Menjabat</th>
                @endif
                @if ($toggleField['isEchelons'])
                    <th class="section-header-color">Eselon</th>
                @endif
                @if ($toggleField['isEchelonDate'])
                    <th class="section-header-color">TMT Eselon</th>
                @endif
                @if ($toggleField['isGrade'])
                    <th class="section-header-color">Pangkat/ Golongan</th>
                @endif
                @if ($toggleField['isEducation'])
                    <th class="section-header-color">Pendidikan Terakhir</th>
                @endif
                @if ($toggleField['isStudyProgram'])
                    <th class="section-header-color">Program Studi</th>
                @endif
                @if ($toggleField['isGradeDate'])
                    <th class="section-header-color">TMT Golongan</th>
                @endif
                @if ($toggleField['isAgency'])
                    <th class="section-header-color">Instansi Induk</th>
                @endif
                @if ($toggleField['isNoWorker'])
                    <th class="section-header-color">No. Karpeg</th>
                @endif
                @if ($toggleField['isKarisu'])
                    <th class="section-header-color">No. Karisu</th>
                @endif
                @if ($toggleField['isNPWP'])
                    <th class="section-header-color">NPWP</th>
                @endif
                @if ($toggleField['isEmployeeStatus'])
                    <th class="section-header-color">Status Pegawai</th>
                @endif
                @if ($toggleField['isNoFamily'])
                    <th class="section-header-color">No. KK</th>
                @endif
                @if ($toggleField['isNIK'])
                    <th class="section-header-color">No. NIK</th>
                @endif
                @if ($toggleField['isComplex'])
                    <th class="section-header-color">Komplek</th>
                @endif
                @if ($toggleField['isIDCardAddress'])
                    <th class="section-header-color">Alamat Tempat Tinggal Saat Ini</th>
                @endif
                @if ($toggleField['isCurrentAddress'])
                    <th class="section-header-color">Alamat Sesuai KTP</th>
                @endif
                @if ($toggleField['isHomeNumber'])
                    <th class="section-header-color">No. Telepon Rumah</th>
                @endif
                @if ($toggleField['isPhoneNumber'])
                    <th class="section-header-color">No. HP</th>
                @endif
                @if ($toggleField['isOfficeAddress'])
                    <th class="section-header-color">Alamat Kantor</th>
                @endif
                @if ($toggleField['isOfficeNumber'])
                    <th class="section-header-color">No. Telepon Kantor</th>
                @endif
                @if ($toggleField['isEmail'])
                    <th class="section-header-color">Email</th>
                @endif
                @if ($toggleField['isOfficeEmail'])
                    <th class="section-header-color">Email Dinas</th>
                @endif
                @if ($toggleField['isPositionDescription'])
                    <th class="section-header-color">Keterangan</th>
                @endif
                @if ($toggleField['isEmergencyContact'])
                    <th class="section-header-color">Kontak Darurat</th>
                @endif
                @if ($toggleField['isPensionCap'])
                    <th class="section-header-color">Maksimal Pensiun</th>
                @endif

                @if ($toggleField['isEducationHistory'])
                    <th class="section-header-color">Riwayat Pendidikan</th>
                @endif
                @if ($toggleField['isPositionHistory'])
                    <th class="section-header-color">Riwayat Jabatan</th>
                @endif
                @if ($toggleField['isGradeHistory'])
                    <th class="section-header-color">Riwayat Golongan</th>
                @endif
                @if ($toggleField['isTrainingStructural'])
                    <th class="section-header-color">Riwayat Pelatihan Struktural</th>
                @endif
                @if ($toggleField['isTrainingFunctional'])
                    <th class="section-header-color">Riwayat Pelatihan Fungsional</th>
                @endif
                @if ($toggleField['isTrainingTechnique'])
                    <th class="section-header-color">Riwayat Pelatihan Teknis</th>
                @endif
                @if ($toggleField['isRecognition'])
                    <th class="section-header-color">Riwayat Penghargaan</th>
                @endif
                @if ($toggleField['isSKP'])
                    <th class="section-header-color">Riwayat SKP</th>
                @endif
                @if ($toggleField['isCredit'])
                    <th class="section-header-color">Riwayat Penetapan Angka Kredit Terakhir</th>
                @endif
                @if ($toggleField['isPerformance'])
                    <th class="section-header-color">Riwayat Penilaian Prestasi Kerja</th>
                @endif
                @if ($toggleField['isDisciplinary'])
                    <th class="section-header-color">Riwayat Hukuman Disiplin</th>
                @endif
                @if ($toggleField['isFamilyHistory'])
                    <th class="section-header-color">Keluarga</th>
                @endif
                @if ($toggleField['isLeave'])
                    <th class="section-header-color">Cuti</th>
                @endif
                @if ($toggleField['isNotes'])
                    <th class="section-header-color">Catatan</th>
                @endif
                @if ($toggleField['isAssessment'])
                    <th class="section-header-color">Hasil Assessment</th>
                @endif
                @if ($toggleField['isCompetency'])
                    <th class="section-header-color">Hasil Uji Kompetensi</th>
                @endif
                @if ($toggleField['isTalentPool'])
                    <th class="section-header-color">Hasil Talent Pool</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php $indexData = 1 @endphp
            @foreach ($userData as $value)
                <tr>
                    <td>{{ $indexData++ }}</td>
                    @if ($toggleField['isName'])
                        <td>{{ $value['name'] }}</td>
                    @endif
                    @if ($toggleField['isTitlePrefix'])
                        <td>{{ $value['title_prefix'] }}</td>
                    @endif
                    @if ($toggleField['isTitleSuffix'])
                        <td>{{ $value['title_suffix'] }}</td>
                    @endif
                    @if ($toggleField['isNameWithTitle'])
                        <td>{{ $value['name_with_title'] }}</td>
                    @endif
                    @if ($toggleField['isNip'])
                        <td>{!! $value['employee_id_number'] !!}/{!! $value['employee_registration_number'] !!}</td>
                    @endif
                    @if ($toggleField['isBirthPlaceDate'])
                        <td>{{ $value['place_of_birth'] }}, {{ $value['date_of_birth'] }}</td>
                    @endif
                    @if ($toggleField['isAge'])
                        <td>{{ $value['age'] }} Tahun</td>
                    @endif
                    @if ($toggleField['isReligion'])
                        <td class="table-section-3-body">
                            @switch($value['religion'])
                                @case(1)
                                    Islam
                                @break

                                @case(2)
                                    Kristen
                                @break

                                @case(3)
                                    Katolik
                                @break

                                @case(4)
                                    Hindu
                                @break

                                @case(5)
                                    Buddha
                                @break

                                @case(6)
                                    Konghucu
                                @break

                                @default
                                    -
                            @endswitch
                        </td>
                    @endif
                    @if ($toggleField['isGender'])
                        <td>{{ $value['gender'] === 1 ? 'Laki-laki' : 'Perempuan' }}</td>
                    @endif
                    @if ($toggleField['isMaritalStatus'])
                        <td class="table-section-3-body">
                            @switch($value['marital_status'])
                                @case(1)
                                    Belum Menikah
                                @break

                                @case(2)
                                    Menikah
                                @break

                                @case(3)
                                    Cerai Hidup
                                @break

                                @case(4)
                                    Cerai Mati
                                @break

                                @default
                                    -
                            @endswitch
                        </td>
                    @endif
                    @if ($toggleField['isMarriageDate'])
                        <td>{{ $value['marriage_date'] }}</td>
                    @endif
                    @if ($toggleField['isMarriageDescription'])
                        <td>{{ $value['marriage_description'] }}</td>
                    @endif
                    @if ($toggleField['isEmployeeType'])
                        <td>
                            <ol>{!! $value['employee_type'] !!}</ol>
                        </td>
                    @endif
                    @if ($toggleField['isAssistanceType'])
                        <td>
                            <ol>{!! $value['assistance_type'] !!}</ol>
                        </td>
                    @endif
                    @if ($toggleField['isOutsourcingType'])
                        <td>
                            <ol>{!! $value['outsource_type'] !!}</ol>
                        </td>
                    @endif
                    @if ($toggleField['isDateCPNS'])
                        <td>{{ $value['cpns_effective_date'] }}</td>
                    @endif
                    @if ($toggleField['isDatePNS'])
                        <td>{{ $value['pns_effective_date'] }}</td>
                    @endif
                    @if ($toggleField['isStartDate'])
                        <td>{{ $value['start_date'] }}</td>
                    @endif
                    @if ($toggleField['isEndDate'])
                        <td>{{ $value['retirement_effective_date'] }}</td>
                    @endif
                    @if ($toggleField['isWorkDuration'])
                        <td>{{ $value['work_duration'] }}</td>
                    @endif
                    @if ($toggleField['isGradeDuration'])
                        <td>{{ $value['grade_duration'] }}</td>
                    @endif
                    @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                        <td>{{ $value['echelon_1'] ?? '' }}</td>
                    @endif
                    @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                        <td>{{ $value['echelon_2'] ?? '' }}</td>
                    @endif
                    @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                        <td>{{ $value['echelon_3'] ?? '' }}</td>
                    @endif
                    @if ($toggleField['isPosition'] || $toggleField['isEchelons'])
                        <td>{{ $value['echelon_4'] ?? '' }}</td>
                    @endif
                    @if ($toggleField['isPosition'])
                        <td>{{ $value['position_name'] }}</td>
                    @endif
                    @if ($toggleField['isFullPosition'])
                        <td>{{ $value['full_position'] }}</td>
                    @endif
                    @if ($toggleField['isDatePosition'])
                        <td>
                            {{ $value['position_effective_date'] }}
                        </td>
                    @endif
                    @if ($toggleField['isEchelons'])
                        <td>{{ $value['echelons_name'] }}</td>
                    @endif
                    @if ($toggleField['isEchelonDate'])
                        <td>
                            {{ $value['echelon_effective_date'] }}
                        </td>
                    @endif
                    @if ($toggleField['isGrade'])
                        <td>{{ $value['grade_name'] }}</td>
                    @endif
                    @if ($toggleField['isEducation'])
                        <td>
                            @php
                                $levels = [
                                    1 => 'SD/Sederajat',
                                    2 => 'SLTP/Sederajat',
                                    3 => 'SLTA/Sederajat',
                                    4 => 'Diploma I/II',
                                    5 => 'Akademik/D3/S.Muda',
                                    6 => 'Diploma IV/Strata I',
                                    7 => 'Strata II',
                                    8 => 'Strata III',
                                ];
                                $educationLabel = $levels[$value['education_level']] ?? '-';
                                $educationLabel = trim(
                                    $educationLabel .
                                        ($value['education_name'] ? ' - ' . $value['education_name'] : '') .
                                        ($value['education_year'] ? ' (' . $value['education_year'] . ')' : ''),
                                );
                            @endphp
                            {{ $educationLabel }}
                        </td>
                    @endif
                    @if ($toggleField['isStudyProgram'])
                        <td>{{ $value['study_program'] ?? '-' }}</td>
                    @endif
                    @if ($toggleField['isGradeDate'])
                        <td>
                            {{ $value['grade_effective_date'] }}
                        </td>
                    @endif
                    @if ($toggleField['isAgency'])
                        <td>{{ $value['institution_name'] }}</td>
                    @endif
                    @if ($toggleField['isNoWorker'])
                        <td>{{ $value['employee_id_card_number'] }}</td>
                    @endif
                    @if ($toggleField['isKarisu'])
                        <td>
                            {{ $value['karisu_number'] }}
                        </td>
                    @endif
                    @if ($toggleField['isNPWP'])
                        <td>{{ $value['id_tax'] }}</td>
                    @endif
                    @if ($toggleField['isEmployeeStatus'])
                        <td class="table-section-3-body">
                            @switch($value['employment_status'])
                                @case(1)
                                    Aktif
                                @break

                                @case(2)
                                    Pensiun
                                @break

                                @case(3)
                                    Berhenti
                                @break

                                @case(4)
                                    Meninggal
                                @break

                                @case(5)
                                    Alih Status
                                @break

                                @case(6)
                                    Aktif PS
                                @break

                                @case(7)
                                    CLTN
                                @break

                                @case(8)
                                    TBLN
                                @break

                                @case(9)
                                    Non Aktif
                                @break

                                @case(10)
                                    Hukuman Disiplin
                                @break

                                @default
                                    -
                            @endswitch
                        </td>
                    @endif
                    @if ($toggleField['isNoFamily'])
                        <td>
                            {{ $value['family_registration_number'] }}
                        </td>
                    @endif
                    @if ($toggleField['isNIK'])
                        <td>
                            {{ $value['id_number'] }}
                        </td>
                    @endif
                    @if ($toggleField['isComplex'])
                        <td>{{ $value['residence_name'] }}</td>
                    @endif
                    @if ($toggleField['isIDCardAddress'])
                        <td>{{ $value['residence_description'] }}</td>
                    @endif
                    @if ($toggleField['isCurrentAddress'])
                        <td>{{ $value['current_address'] }}</td>
                    @endif
                    @if ($toggleField['isHomeNumber'])
                        <td>{{ $value['home_phone_number'] }}</td>
                    @endif
                    @if ($toggleField['isPhoneNumber'])
                        <td>{{ $value['mobile_phone'] }}</td>
                    @endif
                    @if ($toggleField['isOfficeAddress'])
                        <td>{{ $value['office_address'] }}</td>
                    @endif
                    @if ($toggleField['isOfficeNumber'])
                        <td>{{ $value['office_phone_number'] }}</td>
                    @endif
                    @if ($toggleField['isEmail'])
                        <td>{{ $value['email'] }}</td>
                    @endif
                    @if ($toggleField['isOfficeEmail'])
                        <td>{{ $value['office_email'] }}</td>
                    @endif
                    @if ($toggleField['isPositionDescription'])
                        <td>{{ $value['description'] }}</td>
                    @endif
                    @if ($toggleField['isEmergencyContact'])
                        <td>
                            {{ $value['emergency_contact'] }}
                        </td>
                    @endif
                    @if ($toggleField['isPensionCap'])
                        <td>{{ $value['pension_cap'] }}</td>
                    @endif

                    @if ($toggleField['isEducationHistory'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['education_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isPositionHistory'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['position_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isGradeHistory'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['grade_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isTrainingStructural'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['structural_training_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isTrainingFunctional'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['functional_training_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isTrainingTechnique'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['technique_training_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isRecognition'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['recognition_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isSKP'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['skp_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isCredit'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['credit_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isPerformance'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['performance_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isDisciplinary'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['disciplinary_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isFamilyHistory'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['family_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isLeave'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['leave_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isNotes'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['notes']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isAssessment'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['assessment_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isCompetency'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['competency_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                    @if ($toggleField['isTalentPool'])
                        <td>
                            <ol>
                                @foreach (explode('<li>', $value['talent_pool_history']) as $key => $v)
                                    @continue($key == 0)
                                    {!! '
                                                                                                                                                                        <li>' .
                                        $key .
                                        '. ' .
                                        str_replace('&', '&amp;', $v) !!}
                                @endforeach
                            </ol>
                        </td>
                    @endif
                </tr>
            @endforeach

        </tbody>
    </table>
