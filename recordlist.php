<?php
session_start();
// 자동 반납을 위한 코드로 날짜가 달라질 때 마다 실행됨
// session에 저장된 time변수의 date값을 보고 실행할지 말지 결정
// autoreturn.php를 실행하여 자동 반납을 실행함
$time = $_SESSION['time'] ?? date('Y/m/d', strtotime('-1 day'));
if(strtotime($time) < strtotime(date('y/m/d'))){
    echo "<script>location.href='autoreturn.php';</script>";
}

// 디비 연결을 위한 코드
$tns = "
(DESCRIPTION=
     (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=LOCALHOST)(PORT=1521)))
     (CONNECT_DATA= (SERVICE_NAME=XE))   
 )
";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';

$id = $_SESSION["id"];

try {    
    $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {    
    echo("에러 내용: ".$e -> getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"> 
    <!-- JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <link rel=”stylesheet” href=”http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css“>
    <link rel="stylesheet" href="list.css">
    <title>PREVIOUS RENTAL LIST</title>
</head>

<body>
    <!--로고와 로그아웃 div => 로그아웃 시 login.php로 이동-->
    <div class="jumbotron">
        <h1 class="text-center"><a href="main.php"> Online Library</a></h1>
        <a class="btn btn-sm btn-outline-dark float-right" href="login.php">LOGOUT</a>
    </div>
    <!--사용자의 id를 가지고 이름과 이메일을 가져와 nav에 띄움-->
            <?php
                $stmt = $conn -> prepare("SELECT NAME, EMAIL
                                            FROM CUSTOMER
                                            WHERE CNO = :id");
                $stmt -> execute(array($id));
                $row = $stmt -> fetch(PDO::FETCH_ASSOC);
            ?>
    <!--사용자의 이름과 각페이지로의 이동을 위한 nav-->
    <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
        <a href="recordlist.php" class="navbar-brand"><?=$row["NAME"]?> 님</a>
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav">
                <li class="nav-item"><a href="booklist.php" class="nav-link">도서 검색</a></li>
                <li class="nav-item"><a href="rentallist.php" class="nav-link">대출 조회</a></li>
                <li class="nav-item"><a href="reservelist.php" class="nav-link">예약 조회</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Previous Rental List</h2>
        </div>
        <hr>
        <!--대출 조회와 대출 기록 각각의 페이지로의 이동을 위한 버튼-->
        <div class="chooselist">
            <a class="btn btn-outline-dark" href="rentallist.php">대출 조회</a>
            <a class="btn btn-outline-dark" href="recordlist.php">대출 기록</a>
        </div>
        <!--이전 대출 기록을 불러와 테이블로 나타냄-->
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>제목</th>
                    <th>저자</th>
                    <th>대출일</th>
                    <th>반납일</th>
                    <th>대출</th>
                </tr>
            </thead>
            <tbody>
                <!--previousrental 과 ebook, author을 조인하여 과거 대출 도서 정보를 보인다-->
                <?php
$stmt = $conn -> prepare("
SELECT E.ISBN AS ISBN, E.TITLE AS TITLE, T.AUTHOR AS AUTHOR, P.DATERENTED AS DATERENTED, 
        P.DATERETURNED AS DATERETURNED, NVL(E.CNO, 0) as ISRENTAL
FROM(SELECT E.ISBN as ISBN, LISTAGG(A.AUTHOR, ', ') WITHIN GROUP(ORDER BY A.AUTHOR) AS AUTHOR
    FROM EBOOK E, AUTHORS A
    WHERE E.ISBN = A.ISBN
    GROUP BY E.ISBN) T JOIN EBOOK E
ON T.ISBN = E.ISBN
JOIN PREVIOUSRENTAL P
ON T.ISBN = P.ISBN
WHERE P.CNO = :id
ORDER BY 1");
$stmt -> execute(array($id));
// 얻은 대출 기록을 하나씩 fetch하여 tr에 집어넣는다
while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
    $btn_mode = ($row["ISRENTAL"] == 0) ? "btn-success" : "btn-warning";
?>
                <tr>
                    <td><?= $row['ISBN'] ?></td>
                    <td>
                        <!--도서 제목 클릭 시 도서 정보를 보여주는 페이지로 이동한다.-->
                        <a href="bookview.php?isbn=<?=$row["ISBN"]?>"><?=$row['TITLE']?></a>
                    </td>
                    <td><?= $row['AUTHOR'] ?></td>
                    <td><?= $row['DATERENTED'] ?></td>
                    <td><?= $row['DATERETURNED'] ?></td>
                    <td>
                        <!--이미 대출된 도서라면 예약, 대출되지 않았다면 대출로 버튼을 만들음-->
                        <!--2가지 상황에 대한 처리를 위해 mode를 정하여 처리 php인 process.php로 이동-->
                        <a class="btn <?=$btn_mode?>"
                            href="process.php?isbn=<?=$row["ISBN"]?>&mode=<?=$row["ISRENTAL"] == 0 ? "rental" : "reserve"?>">
                            <?=$row["ISRENTAL"] == 0 ? "대출" : "예약"?>
                        </a>
                    </td>
                </tr>
                <?php
}
?>
            </tbody>
        </table>

        <div>

        </div>

    </div>
</body>

</html>