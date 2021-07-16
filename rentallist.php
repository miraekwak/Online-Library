<?php
// 자동 반납을 위한 코드로 날짜가 달라질 때 마다 실행됨
// 도서 목록이 보여지는 곳에서만 자동반납 프로세스를 확인함
// session에 저장된 time변수의 date값을 보고 실행할지 말지 결정
// autoreturn.php를 실행하여 자동 반납을 실행함
session_start();
$time = $_SESSION['time'] ?? date('Y/m/d', strtotime('-1 day'));
if(strtotime($time) < strtotime(date('y/m/d'))){
    echo "<script>location.href='autoreturn.php';</script>";
}

// 디비 연결
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
    <title>RENTAL LIST</title>
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
        <a href="rentallist.php" class="navbar-brand"><?=$row["NAME"]?> 님</a>
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav">
                <li class="nav-item"><a href="booklist.php" class="nav-link">도서 검색</a></li>
                <li class="nav-item"><a href="rentallist.php" class="nav-link">대출 조회</a></li>
                <li class="nav-item"><a href="reservelist.php" class="nav-link">예약 조회</a></li>
            </ul>
        </div>
    </nav>

    <!-- 페이지 내용 div-->
    <div class="container">
        <!-- 현재 페이지 제목-->
        <div class="page-header">
            <h2>Rental List</h2>
        </div>
        <hr>
        <!--대출 조회와 대출 기록 각각의 페이지로의 이동을 위한 버튼-->
        <div class="chooselist">
            <a class="btn btn-outline-dark" href="rentallist.php">대출 조회</a>
            <a class="btn btn-outline-dark" href="recordlist.php">대출 기록</a>
        </div>

        <!--현재 대출 도서를 테이블로 나타냄-->
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>제목</th>
                    <th>저자</th>
                    <th>대출일</th>
                    <th>반납기한</th>
                    <th>연장횟수</th>
                    <th>반납</th>
                    <th>연장</th>
                </tr>
            </thead>
            <tbody>
                <?php
                //authors와 ebook을 join하여 공동 저자 처리 후 ebook의 cno를 확인하여 대출 도서 select
$stmt = $conn -> prepare("
SELECT E.ISBN AS ISBN, E.TITLE AS TITLE, T.AUTHOR AS AUTHOR, 
        E.DATERENTED AS DATERENTED, E.DATEDUE AS DATEDUE, E.EXTTIMES AS EXTTIMES
FROM(SELECT E.ISBN as ISBN, LISTAGG(A.AUTHOR, ', ') WITHIN GROUP(ORDER BY A.AUTHOR) AS AUTHOR
    FROM EBOOK E, AUTHORS A
    WHERE E.ISBN = A.ISBN
    GROUP BY E.ISBN) T JOIN EBOOK E
ON T.ISBN = E.ISBN
WHERE E.CNO = :id
ORDER BY 1");
// 실행후 대출 도서 목록 fetch
$stmt -> execute(array($id));
while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
?>
                <tr>
                    <td><?= $row['ISBN'] ?></td>
                    <td>
                        <!--도서 제목 클릭 시 도서 정보를 보여주는 페이지로 이동한다.-->
                        <a href="bookview.php?isbn=<?=$row["ISBN"]?>"><?=$row['TITLE']?></a>
                    </td>
                    <td><?= $row['AUTHOR'] ?></td>
                    <td><?= $row['DATERENTED'] ?></td>
                    <td><?= $row['DATEDUE'] ?></td>
                    <td><?= $row['EXTTIMES'] ?></td>
                    <!--대출된 도서에 대해 가능한 반납과 연장에 대해 버튼을 만들고 process.php에 mode를 get방식으로 전송함-->
                    <td>
                        <a class="btn btn-success" href="process.php?isbn=<?=$row["ISBN"]?>&mode=return">반납</a>
                    </td>
                    <td>
                        <a class="btn btn-info" href="process.php?isbn=<?=$row["ISBN"]?>&mode=extension">연장</a>
                    </td>
                </tr>
                <?php
}
?>
            </tbody>
        </table>

    </div>
</body>

</html>