<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket umugenzi</title>
    <style>
        // Variables
        $background-color: Thistle;
        $width: 400;
        $circle-size: 50;

        body {
            background-color: $background-color;
            font-family: 'Yanone Kaffeesatz', sans-serif;
            font-weight: 600;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .ticket {
            width: $width + px;
            height: 775px;
            background-color: white;
            margin: 25px auto;
            position: relative;
        }

        .holes-top {
            height: $circle-size + px;
            width: $circle-size + px;
            background-color: $background-color;
            border-radius: 50%;
            position: absolute;
            left:50%;
            margin-left: ($circle-size / -2) + px;
            top: ($circle-size / -2) + px;
            
            &:before,
            &:after {
                content: '';
                height: $circle-size + px;
                width: $circle-size + px;
                background-color: $background-color;
                position: absolute;
                border-radius: 50%;
            }
            &:before {
                left: ($width / -2) + px;
            }
            &:after {
                left: ($width / 2) + px;
            }
        }

        .holes-lower {
            position: relative;
            margin:25px;
            border:1px dashed #aaa;
            &:before,
            &:after {
                content: '';
                height: $circle-size + px;
                width: $circle-size + px;
                background-color: $background-color;
                position: absolute;
                border-radius: 50%;
            }
            
            &:before {
                top: -25px;
                left: ($circle-size / -1) + px;
            }
            &:after {
                top: -25px;
                left: ($width - $circle-size) + px;
            }
        }

        .title {
            padding: 50px 25px 10px;
        }

        .cinema {
            color: #aaa;
            font-size: 22px;
        }

        .movie-title {
            font-size: 50px;
        }
        .info {
            padding: 15px 25px;
        }
        table {
            width: 100%;
            font-size: 18px;
            margin-bottom: 15px;
            tr {
                margin-bottom: 10px;
            }
            
            th {
                text-align: left;
                &:nth-of-type(1) {
                    width: 38%;
                }
                &:nth-of-type(2) {
                    width: 40%;
                }
                &:nth-of-type(3) {
                    width: 15%;
                }
            }
            
            td {
                width: 33%;
                font-size: 32px;
            }
        }
        .bigger {
            font-size: 48px;
        }
        .serial {
            padding: 25px;
            table {
                border-collapse: collapse;
                margin: 0 auto;
            }
            td {
                width: 3px;
                height: 50px;
            }
        }
        .numbers {
            td {
                font-size: 16px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="holes-top"></div>
        <div class="title">
            <p class="cinema">ODEON CINEMA PRESENTS</p>
            <p class="movie-title">ONLY GOD FORGIVES</p>
        </div>
        <div class="poster">
            <img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/25240/only-god-forgives.jpg" alt="Movie: Only God Forgives" />
        </div>
        <div class="info">
            <table>
                <tr>
                    <th>SCREEN</th>
                    <th>ROW</th>
                    <th>SEAT</th>
                </tr>
                <tr>
                    <td class="bigger">18</td>
                    <td class="bigger">H</td>
                    <td class="bigger">24</td>
                </tr>
            </table>
            <table>
                <tr>
                    <th>PRICE</th>
                    <th>DATE</th>
                    <th>TIME</th>
                </tr>
                <tr>
                    <td>$12.00</td>
                    <td>1/13/17</td>
                    <td>19:30</td>
                </tr>
            </table>
        </div>
        <div class="holes-lower"></div>
        <div class="serial">
            <table class="barcode">
                <tr></tr>
            </table>
            <table class="numbers">
                <tr>
                    <td>9</td>
                    <td>1</td>
                    <td>7</td>
                    <td>3</td>
                    <td>7</td>
                    <td>5</td>
                    <td>4</td>
                    <td>4</td>
                    <td>4</td>
                    <td>5</td>
                    <td>4</td>
                    <td>1</td>
                    <td>4</td>
                    <td>7</td>
                    <td>8</td>
                    <td>7</td>
                    <td>3</td>
                    <td>4</td>
                    <td>1</td>
                    <td>4</td>
                    <td>5</td>
                    <td>2</td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        var code = '11010010000100111011001011101111011010001110101110011001101110010010111101110111001011001001000011011000111010110001001110111101101001011010111000101101'

        table = $('.barcode tr');
        for(var i = 0; i < code.length; i++) {
            if( code[i]==1 ) {
                table.append('<td bgcolor="black">')
            } else {
                table.append('<td bgcolor="white">')
            }
        }
    </script>
</body>
</html>