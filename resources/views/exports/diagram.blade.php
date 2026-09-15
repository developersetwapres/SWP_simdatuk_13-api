<!-- resources/views/orgchart.blade.php -->

<!DOCTYPE html>
<html>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <style>
        html * {
            font-family: Inter !important;
        }

        body {
            background-color: #F4F4F4;
        }

        .page_break {
            page-break-after: always;
        }

        .orgchart ul {
            padding-top: 40px;
            position: relative;
            padding-left: 0;
            list-style: none;
            text-align: center;
        }

        .ul-child::before {
            content: '';
            display: table;
            position: absolute;
            top: 0;
            /* left: 49.82%; */
            left: 50%;
            border-left: 1px solid black;
            width: 0;
            height: 40px;
        }

        .orgchart li {
            display: inline-block;
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 40px 5px 0 5px;
            vertical-align: top;
        }

        .orgchart li::after {
            right: auto;
            left: 50%;
            border-left: 1px solid black;
        }

        .orgchart li:only-child::after,
        .orgchart li:only-child::before {
            display: none;
        }

        .orgchart li:only-child {
            padding-top: 0;
        }

        .li-left::before {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 1px solid black;
            width: 50%;
            height: 40px;
        }

        .li-single::before {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border: none;
            width: 50%;
            height: 40px;
        }

        .li-single:last-child::before {
            /* left: -1%; */
            border-right: 1px solid black;
        }

        .orgchart li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 1px solid black;
            border-radius: 5px 0 0 0;
            width: 50%;
            height: 40px;
        }

        .orgchart li:last-child::after {
            border: 0 none;
        }

        /* .orgchart li:last-child::before {
            border-right: 1px solid purple;
            border-radius: 0 5px 0 0;
        } */

        .li-last::before {
            border-right: 1px solid black;
            border-radius: 0 5px 0 0;
        }

        .orgchart li:first-child::after {
            border-radius: 5px 0 0 0;
        }

        .node-person-stack-container {
            width: 240px;
            height: 600px;
        }

        .node-person {
            border: 1px solid #ccc;
            padding: 5px 10px;
            text-decoration: none;
            color: #666;
            display: inline-block;
            border-radius: 12px;
            background-color: white;
            width: 200px;
            height: 620px;
        }

        .node-person-stack {
            border: 1px solid #ccc;
            padding: 5px 10px;
            text-decoration: none;
            color: #666;
            display: inline-block;
            border-radius: 12px;
            background-color: white;
            width: 200px;
            height: 620px;
            position: absolute;
        }

        .node-non-person {
            border: 1px solid #ccc;
            padding: 5px 10px;
            text-decoration: none;
            color: #666;
            display: inline-block;
            border-radius: 12px;
            background-color: white;
            width: 480px;

        }

        .node-position-name-person {
            font-size: 14px;
            font-weight: 700;
            color: #394346;
            text-align: center;
            height: 90px;
        }

        .node-position-name-non-person {
            font-size: 14px;
            font-weight: 700;
            color: #394346;
            text-align: center;
        }

        .node-photo-container {
            text-align: center;
        }

        .node-photo {
            width: 120px;
            height: 160px;
            margin-top: 20px;
        }

        .node-user-name {
            font-size: 16px;
            font-weight: 700;
            color: #895700;
            text-align: center;
        }

        .node-item-title {
            font-size: 14px;
            font-weight: 400;
            color: #394346;
            word-wrap: break-word;
        }

        .node-item-value {
            font-size: 14px;
            font-weight: 700;
            color: #394346;
            word-wrap: break-word;
        }

        .node-person-stack:nth-child(3) {
            transform: translateY(10px) translateX(10px);
            z-index: 3;
        }

        .node-person-stack:nth-child(2) {
            transform: translateY(5px) translateX(5px);
            z-index: 2;
        }

        .node-person-stack:nth-child(1) {
            z-index: 1;
        }
    </style>
</head>

<body>
    <div class="orgchart">
        {!! $html !!}
    </div>
</body>

</html>
