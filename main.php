<?php
//디비 연결
session_start();
$id = $_SESSION["id"];
$tns = "
   (DESCRIPTION=
        (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=LOCALHOST)(PORT=1521)))
        (CONNECT_DATA= (SERVICE_NAME=XE))   
    )
";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';
try {    
    $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {    
    echo("에러 내용: ".$e -> getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"> 
    <!-- JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <link rel=”stylesheet” href=”http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css“>
    <link rel="stylesheet" href="main.css">
    <title>Online Library</title>
</head>

<body>
    <!--로고와 로그아웃 div => 로그아웃 시 login.php로 이동-->
    <div class="jumbotron">
        <h1 class="text-center"><a href="main.php"> Online Library</a></h1>
        <a class="btn btn-sm btn-outline-dark float-right" href="login.php">LOGOUT</a>
    </div>

    <!--현재 로그인한 고객 정보를 보여주는 div로 id로 데이터를 select하여 이름, cno, email을 보임-->
    <div class="info">
        <?php
                $stmt = $conn -> prepare("SELECT NAME, EMAIL
                                            FROM CUSTOMER
                                            WHERE CNO = :id");
                $stmt -> execute(array($id));
                $row = $stmt -> fetch(PDO::FETCH_ASSOC);
            ?>
        <span><?=$row["NAME"]?> 님</span>
        <span>ID : <?=$id?></span>
        <span>email : <?=$row["EMAIL"]?></span>

    </div>

    <!--크게 3가지의 경우로 나누어 각각 페이지 연결-->
    <div class="container">
        <div class="row">
            <div class="btn btn-outline-dark btn-lg mvp">
                <a href="booklist.php">도서 검색</a>
            </div>
            <div class="text">
                - 도서 목록 확인<br>
                - 도서 대출 또는 예약 가능
            </div>
        </div>

        <div class="row">
            <div class="btn btn-outline-dark btn-lg mvp">
                <a href="rentallist.php">대출 조회</a>
            </div>
            <div class="text">
                - 대출된 도서에 대한 반납, 기간 연장 가능<br>
                - 과거 대출된 도서 기록 확인
            </div>
        </div>

        <div class="row">
            <div class="btn btn-outline-dark btn-lg mvp">
                <a href="reservelist.php">예약 조회</a>
            </div>
            <div class="text">
                - 예약된 도서 조회<br>
                - 예약 취소
            </div>
        </div>

    </div>
</body>

</html>